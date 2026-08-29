<?php

defined('DS') or exit('No direct access.');

use System\Hook;
use System\Database;
use System\Database\Schema;

/**
 * Covers the events a model fires as it goes through its life, the observers
 * that listen for them, and calling an operation off from one.
 */
class FacileEventsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The events a model can fire.
     *
     * @var array
     */
    protected $events = [
        'retrieved', 'creating', 'created', 'updating', 'updated', 'saving',
        'saved', 'deleting', 'deleted', 'restoring', 'restored', 'replicating',
    ];

    /**
     * Setup.
     */
    public function setUp()
    {
        Database::$connections = [];

        Schema::create('event_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        EventObserver::$seen = [];
        BootedModel::$seen = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        foreach ($this->events as $event) {
            Hook::clear('facile.' . $event);
            Hook::clear('facile.' . $event . ': EventModel');
        }

        Schema::drop_if_exists('event_models');
        Database::$connections = [];
    }

    /**
     * Write down every event the model fires, in the order it fires them.
     *
     * @param array $order
     *
     * @return void
     */
    protected function record(array &$order)
    {
        foreach ($this->events as $event) {
            Hook::listen('facile.' . $event . ': EventModel', function ($model) use ($event, &$order) {
                $order[] = $event;
            });
        }
    }

    // -------------------------------------------------------------------------
    // The events themselves
    // -------------------------------------------------------------------------

    /**
     * Inserting a row fires saving, creating, created and saved, in that order.
     *
     * @group system
     */
    public function testEventsOfAnInsert()
    {
        $order = [];
        $this->record($order);

        EventModel::create(['name' => 'satu']);

        $this->assertEquals(['saving', 'creating', 'created', 'saved'], $order);
    }

    /**
     * Updating a row fires saving, updating, updated and saved.
     *
     * @group system
     */
    public function testEventsOfAnUpdate()
    {
        $model = EventModel::create(['name' => 'satu']);

        $order = [];
        $this->record($order);

        $model->name = 'dua';
        $model->save();

        $this->assertEquals(['saving', 'updating', 'updated', 'saved'], $order);
    }

    /**
     * A model that comes back from the database says so.
     *
     * @group system
     */
    public function testRetrievedFiresOnHydration()
    {
        $model = EventModel::create(['name' => 'satu']);

        $order = [];
        $this->record($order);

        EventModel::find($model->get_key());

        $this->assertEquals(['retrieved'], $order);
    }

    /**
     * Deleting, restoring and replicating fire their own.
     *
     * @group system
     */
    public function testEventsOfTheRestOfTheLife()
    {
        $model = EventModel::create(['name' => 'satu']);

        $order = [];
        $this->record($order);

        $model->delete();
        $this->assertEquals(['deleting', 'deleted'], $order);

        $order = [];
        $trashed = EventModel::with_trashed()->where('id', '=', $model->get_key())->first();
        $order = [];

        $trashed->restore();
        $this->assertEquals(['restoring', 'restored'], $order);

        $order = [];
        $trashed->replicate();
        $this->assertEquals(['replicating'], $order);

        $order = [];
        $trashed->force_delete();
        $this->assertEquals(['deleting', 'deleted'], $order);
    }

    /**
     * A listener may be registered without naming the model, for every model.
     *
     * @group system
     */
    public function testTheEventIsFiredWithoutTheModelNameToo()
    {
        $seen = 0;

        Hook::listen('facile.saving', function ($model) use (&$seen) {
            $seen++;
        });

        EventModel::create(['name' => 'satu']);

        $this->assertEquals(1, $seen);
    }

    // -------------------------------------------------------------------------
    // Calling an operation off
    // -------------------------------------------------------------------------

    /**
     * A listener that answers with FALSE calls the insert off, and nothing is
     * written.
     *
     * @group system
     */
    public function testCreatingCanBeCalledOff()
    {
        Hook::listen('facile.creating: EventModel', function ($model) {
            return false;
        });

        $model = new EventModel(['name' => 'satu']);

        $this->assertFalse($model->save());
        $this->assertFalse($model->exists);
        $this->assertEquals(0, EventModel::count());
    }

    /**
     * Calling the save off leaves the model as it was, timestamps included.
     *
     * @group system
     */
    public function testSavingCanBeCalledOffBeforeAnythingIsTouched()
    {
        Hook::listen('facile.saving: EventModel', function ($model) {
            return false;
        });

        $model = new EventModel(['name' => 'satu']);

        $this->assertFalse($model->save());
        $this->assertNull($model->created_at);
        $this->assertNull($model->updated_at);
    }

    /**
     * An update that is called off does not reach the database.
     *
     * @group system
     */
    public function testUpdatingCanBeCalledOff()
    {
        $model = EventModel::create(['name' => 'satu']);

        Hook::listen('facile.updating: EventModel', function ($model) {
            return false;
        });

        $model->name = 'dua';

        $this->assertFalse($model->save());
        $this->assertEquals('satu', EventModel::find($model->get_key())->name);
    }

    /**
     * A delete that is called off leaves the row where it is.
     *
     * @group system
     */
    public function testDeletingCanBeCalledOff()
    {
        $model = EventModel::create(['name' => 'satu']);

        Hook::listen('facile.deleting: EventModel', function ($model) {
            return false;
        });

        $this->assertFalse($model->delete());
        $this->assertNotNull(EventModel::find($model->get_key()));
    }

    /**
     * So does a restore that is called off.
     *
     * @group system
     */
    public function testRestoringCanBeCalledOff()
    {
        $model = EventModel::create(['name' => 'satu']);
        $model->delete();

        Hook::listen('facile.restoring: EventModel', function ($model) {
            return false;
        });

        $trashed = EventModel::with_trashed()->where('id', '=', $model->get_key())->first();

        $this->assertFalse($trashed->restore());
        $this->assertNull(EventModel::find($model->get_key()));
    }

    /**
     * One listener out of several is enough to call the operation off, and
     * only an answer of FALSE does it.
     *
     * @group system
     */
    public function testOnlyFalseCallsTheOperationOff()
    {
        Hook::listen('facile.saving: EventModel', function ($model) {
            return 'sesuatu';
        });

        $this->assertTrue(EventModel::create(['name' => 'satu'])->exists);

        Hook::listen('facile.saving: EventModel', function ($model) {
            return false;
        });
        Hook::listen('facile.saving: EventModel', function ($model) {
            return null;
        });

        $model = new EventModel(['name' => 'dua']);

        $this->assertFalse($model->save());
    }

    // -------------------------------------------------------------------------
    // Observers and booting
    // -------------------------------------------------------------------------

    /**
     * An observer is listened to for every method named after an event, and
     * for nothing else.
     *
     * @group system
     */
    public function testObserver()
    {
        EventModel::observe('EventObserver');

        EventModel::create(['name' => 'satu']);

        $this->assertEquals(['creating', 'created'], EventObserver::$seen);
    }

    /**
     * An observer may be given as an instance.
     *
     * @group system
     */
    public function testObserverAsAnInstance()
    {
        EventModel::observe(new EventObserver());

        EventModel::create(['name' => 'satu']);

        $this->assertEquals(['creating', 'created'], EventObserver::$seen);
    }

    /**
     * boot() runs once for the model, however many of them are made.
     *
     * @group system
     */
    public function testBootRunsOnce()
    {
        new BootedModel();
        new BootedModel();
        new BootedModel();
        BootedModel::count();

        $this->assertEquals(1, BootedModel::$booted_times);
    }

    /**
     * What boot() registers is listened for like anything else.
     *
     * @group system
     */
    public function testBootRegistersItsListeners()
    {
        BootedModel::create(['name' => 'satu']);

        $this->assertEquals(['boot.creating'], BootedModel::$seen);
    }

    /**
     * An event name handed a closure registers a listener for it.
     *
     * @group system
     */
    public function testEventNameTakesAClosure()
    {
        $seen = false;

        EventModel::creating(function ($model) use (&$seen) {
            $seen = true;
        });

        EventModel::create(['name' => 'satu']);

        $this->assertTrue($seen);
    }

    /**
     * Which does not get in the way of the query builder, or of a scope.
     *
     * @group system
     */
    public function testTheQueryBuilderIsStillReachable()
    {
        EventModel::create(['name' => 'satu']);
        EventModel::create(['name' => 'dua']);

        $this->assertEquals(2, EventModel::count());
        $this->assertEquals(1, EventModel::where('name', '=', 'satu')->count());
        $this->assertEquals(1, EventModel::named('dua')->count());
    }

    /**
     * The listeners of one model are not the listeners of another.
     *
     * @group system
     */
    public function testListenersBelongToTheirOwnModel()
    {
        $seen = 0;

        Hook::listen('facile.creating: EventModel', function ($model) use (&$seen) {
            $seen++;
        });

        BootedModel::create(['name' => 'satu']);

        $this->assertEquals(0, $seen);
    }

    /**
     * An event nobody listens for costs nothing, which is what keeps
     * 'retrieved' from being fired for every row of every result.
     *
     * @group system
     */
    public function testAnEventNobodyListensForIsNotFired()
    {
        for ($i = 0; $i < 20; $i++) {
            EventModel::create(['name' => 'baris ' . $i]);
        }

        $tracking = new \ReflectionProperty('\System\Foundation\Oops\Collectors', 'trackEvents');
        $tracked = new \ReflectionProperty('\System\Foundation\Oops\Collectors', 'data');
        PHP_VERSION_ID < 80100 && $tracking->setAccessible(true);
        PHP_VERSION_ID < 80100 && $tracked->setAccessible(true);

        $was = $tracking->getValue();
        $production = \System\Foundation\Oops\Debugger::$productionMode;

        $tracking->setValue(null, true);
        \System\Foundation\Oops\Debugger::$productionMode = false;

        try {
            $data = $tracked->getValue();
            $before = isset($data['events']) ? count($data['events']) : 0;

            // With nobody listening, hydrating twenty rows adds nothing.
            EventModel::all();

            $data = $tracked->getValue();
            $quiet = isset($data['events']) ? count($data['events']) : 0;

            // With a listener, every row of the result is announced.
            Hook::listen('facile.retrieved: EventModel', function ($model) {
            });

            EventModel::all();

            $data = $tracked->getValue();
            $loud = isset($data['events']) ? count($data['events']) : 0;
        } catch (\Exception $e) {
            $tracking->setValue(null, $was);
            \System\Foundation\Oops\Debugger::$productionMode = $production;

            throw $e;
        }

        $tracking->setValue(null, $was);
        \System\Foundation\Oops\Debugger::$productionMode = $production;

        // The query itself is announced either way; what must not happen is one
        // line per row of the result.
        $this->assertLessThan(20, $quiet - $before);
        $this->assertGreaterThanOrEqual(20, $loud - $quiet);
    }
}

/**
 * The model whose life is being watched.
 */
class EventModel extends \System\Database\Facile\Model
{
    public static $table = 'event_models';

    public static $soft_delete = true;

    public static $accessible = ['name'];

    /**
     * Narrow the query down to the rows of the given name.
     *
     * @param \System\Database\Query $query
     * @param string                 $name
     *
     * @return \System\Database\Query
     */
    public function scope_named($query, $name)
    {
        return $query->where('name', '=', $name);
    }
}

/**
 * A model that registers what it needs while booting.
 */
class BootedModel extends \System\Database\Facile\Model
{
    public static $table = 'event_models';

    public static $accessible = ['name'];

    public static $booted_times = 0;

    public static $seen = [];

    /**
     * Register the listeners of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        static::$booted_times++;

        static::creating(function ($model) {
            BootedModel::$seen[] = 'boot.creating';
        });
    }
}

/**
 * An observer with two methods named after events, and one that is not.
 */
class EventObserver
{
    public static $seen = [];

    /**
     * Watch the insert before it happens.
     *
     * @param \System\Database\Facile\Model $model
     *
     * @return void
     */
    public function creating($model)
    {
        static::$seen[] = 'creating';
    }

    /**
     * Watch the insert after it happened.
     *
     * @param \System\Database\Facile\Model $model
     *
     * @return void
     */
    public function created($model)
    {
        static::$seen[] = 'created';
    }

    /**
     * A method that is not named after an event, and is left alone.
     *
     * @param \System\Database\Facile\Model $model
     *
     * @return void
     */
    public function something_else($model)
    {
        static::$seen[] = 'TIDAK BOLEH';
    }
}
