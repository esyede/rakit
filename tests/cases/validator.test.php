<?php

defined('DS') or exit('No direct script access.');

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Config::set('database.default', 'sqlite');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $_FILES = [];
    }

    /**
     * Test untuk rule 'required'.
     *
     * @group system
     */
    public function testRequiredRule()
    {
        $input = ['name' => 'Budi Purnomo'];
        $rules = ['name' => 'required'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['name'] = '';
        $this->assertFalse(Validator::make($input, $rules)->valid());

        unset($input['name']);
        $this->assertFalse(Validator::make($input, $rules)->valid());

        $_FILES['name']['tmp_name'] = 'foo';
        $this->assertTrue(Validator::make($_FILES, $rules)->valid());

        $_FILES['name']['tmp_name'] = '';
        $this->assertFalse(Validator::make($_FILES, $rules)->valid());
    }

    /**
     * Test untuk rule 'confirmed'.
     *
     * @group system
     */
    public function testTheConfirmedRule()
    {
        $input = ['password' => 'foo', 'password_confirmation' => 'foo'];
        $rules = ['password' => 'confirmed'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['password_confirmation'] = 'foo_bar';
        $this->assertFalse(Validator::make($input, $rules)->valid());

        unset($input['password_confirmation']);
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'different'.
     *
     * @group system
     */
    public function testTheDifferentRule()
    {
        $input = ['password' => 'foo', 'password_confirmation' => 'bar'];
        $rules = ['password' => 'different:password_confirmation'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['password_confirmation'] = 'foo';
        $this->assertFalse(Validator::make($input, $rules)->valid());

        unset($input['password_confirmation']);
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'accepted'.
     *
     * @group system
     */
    public function testTheAcceptedRule()
    {
        $input = ['terms' => '1'];
        $rules = ['terms' => 'accepted'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['terms'] = 'yes';
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['terms'] = '2';
        $this->assertFalse(Validator::make($input, $rules)->valid());

        // The accepted rule implies required, so should fail if field not present.
        unset($input['terms']);
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'boolean'.
     *
     * @group system
     */
    public function testTheBooleanRule()
    {
        $input = [];
        $inputs = [true, false, 0, 1, '0', '1'];
        $rules = ['input' => 'boolean'];

        for ($i = 0; $i < count($inputs); $i++) {
            $input = ['input' => $inputs[$i]];
            $this->assertTrue(Validator::make($input, $rules)->valid());
        }

        $inputs = ['true', 'false', 'yes', 'no'];

        for ($i = 0; $i < count($inputs); $i++) {
            $input = ['input' => $inputs[$i]];
            $this->assertFalse(Validator::make($input, $rules)->valid());
        }
    }

    /**
     * Test untuk rule 'before'.
     *
     * @group system
     */
    public function testTheBeforeRule()
    {
        $rules = ['date' => 'before:2020-10-12 12:00:01'];

        $input = ['date' => '2020-10-12 12:00:00'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input = ['date' => '2020-10-12 12:00:01'];
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'before_or_equals'.
     *
     * @group system
     */
    public function testTheBeforeOrEqualsRule()
    {
        $rules = ['date' => 'before_or_equals:2020-10-12 12:00:01'];

        $input = ['date' => '2020-10-12 12:00:00'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input = ['date' => '2020-10-12 12:00:01'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input = ['date' => '2020-10-12 12:00:02'];
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'numeric'.
     *
     * @group system
     */
    public function testTheNumericRule()
    {
        $input = ['amount' => '1.23'];
        $rules = ['amount' => 'numeric'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['amount'] = '1';
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['amount'] = 1.2;
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['amount'] = '1.2a';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'integer'.
     *
     * @group system
     */
    public function testTheIntegerRule()
    {
        $input = ['amount' => '1'];
        $rules = ['amount' => 'integer'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['amount'] = '0';
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['amount'] = 1.2;
        $this->assertFalse(Validator::make($input, $rules)->valid());

        $input['amount'] = '1.2a';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'size'.
     *
     * @group system
     */
    public function testTheSizeRule()
    {
        $input = ['amount' => '1.23'];
        $rules = ['amount' => 'numeric|size:1.23'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $rules = ['amount' => 'numeric|size:1'];
        $this->assertFalse(Validator::make($input, $rules)->valid());

        // Jika tidak ada rule 'numeric', perlakukan dia sebagai string.
        $input = ['amount' => '111'];
        $rules = ['amount' => 'size:3'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $rules = ['amount' => 'size:4'];
        $this->assertFalse(Validator::make($input, $rules)->valid());

        // Jika merupakan sebuah file, cek ukurannya dalam Kilobytes (KB)
        $_FILES['photo']['tmp_name'] = 'foo';
        $_FILES['photo']['size'] = 10240;
        $rules = ['photo' => 'size:10'];
        $this->assertTrue(Validator::make($_FILES, $rules)->valid());

        $_FILES['photo']['size'] = 14000;
        $this->assertFalse(Validator::make($_FILES, $rules)->valid());
    }

    /**
     * Test untuk rule 'between'.
     *
     * @group system
     */
    public function testTheBetweenRule()
    {
        $input = ['amount' => '1.23'];
        $rules = ['amount' => 'numeric|between:1,2'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $rules = ['amount' => 'numeric|between:2,3'];
        $this->assertFalse(Validator::make($input, $rules)->valid());

        // Jika tidak ada rule 'numeric', perlakukan dia sebagai string.
        $input = ['amount' => '111'];
        $rules = ['amount' => 'between:1,3'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $rules = ['amount' => 'between:100,111'];
        $this->assertFalse(Validator::make($input, $rules)->valid());

        // Jika merupakan sebuah file, cek ukurannya dalam Kilobytes (KB)
        $_FILES['photo']['tmp_name'] = 'foo';
        $_FILES['photo']['size'] = 10240;
        $rules = ['photo' => 'between:9,11'];
        $this->assertTrue(Validator::make($_FILES, $rules)->valid());

        $_FILES['photo']['size'] = 14000;
        $this->assertFalse(Validator::make($_FILES, $rules)->valid());
    }

    /**
     * Test untuk rule 'min'.
     *
     * @group system
     */
    public function testTheMinRule()
    {
        $input = ['amount' => '1.23'];
        $rules = ['amount' => 'numeric|min:1'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $rules = ['amount' => 'numeric|min:2'];
        $this->assertFalse(Validator::make($input, $rules)->valid());

        // Jika tidak ada rule 'numeric', perlakukan dia sebagai string.
        $input = ['amount' => '01'];
        $rules = ['amount' => 'min:2'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $rules = ['amount' => 'min:3'];
        $this->assertFalse(Validator::make($input, $rules)->valid());

        // Jika merupakan sebuah file, cek ukurannya dalam Kilobytes (KB)
        $_FILES['photo']['tmp_name'] = 'foo';
        $_FILES['photo']['size'] = 10240;
        $rules = ['photo' => 'min:9'];
        $this->assertTrue(Validator::make($_FILES, $rules)->valid());

        $_FILES['photo']['size'] = 8000;
        $this->assertFalse(Validator::make($_FILES, $rules)->valid());
    }

    /**
     * Test untuk rule 'max'.
     *
     * @group system
     */
    public function testTheMaxRule()
    {
        $input = ['amount' => '1.23'];
        $rules = ['amount' => 'numeric|max:2'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $rules = ['amount' => 'numeric|max:1'];
        $this->assertFalse(Validator::make($input, $rules)->valid());

        // Jika tidak ada rule 'numeric', perlakukan dia sebagai string.
        $input = ['amount' => '01'];
        $rules = ['amount' => 'max:3'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $rules = ['amount' => 'max:1'];
        $this->assertFalse(Validator::make($input, $rules)->valid());

        // Jika merupakan sebuah file, cek ukurannya dalam Kilobytes (KB)
        $_FILES['photo']['tmp_name'] = 'foo';
        $_FILES['photo']['size'] = 10240;
        $rules = ['photo' => 'max:11'];
        $this->assertTrue(Validator::make($_FILES, $rules)->valid());

        $_FILES['photo']['size'] = 140000;
        $this->assertFalse(Validator::make($_FILES, $rules)->valid());
    }

    /**
     * Test untuk rule 'in'.
     *
     * @group system
     */
    public function testTheInRule()
    {
        $input = ['size' => 'L'];
        $rules = ['size' => 'in:S,M,L'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['size'] = 'XL';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'not_in'.
     *
     * @group system
     */
    public function testTheNotInRule()
    {
        $input = ['size' => 'L'];
        $rules = ['size' => 'not_in:S,M,L'];
        $this->assertFalse(Validator::make($input, $rules)->valid());

        $input['size'] = 'XL';
        $this->assertTrue(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'ip'.
     *
     * @group system
     */
    public function testTheIPRule()
    {
        $input = ['ip' => '192.168.1.1'];
        $rules = ['ip' => 'ip'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['ip'] = '192.111';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'email'.
     *
     * @group system
     */
    public function testTheEmailRule()
    {
        $input = ['email' => 'example@gmail.com'];
        $rules = ['email' => 'email'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['email'] = 'asdasdasd';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'url'.
     *
     * @group system
     */
    public function testTheUrlRule()
    {
        $input = ['url' => 'https://www.site.com'];
        $rules = ['url' => 'url'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['url'] = 'asdasdasd';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'active_url'.
     *
     * @group system
     */
    public function testTheActiveUrlRule()
    {
        $input = [];
        $rules = ['url' => 'active_url'];

        $input['url'] = 'https://site.com';
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['url'] = 'https://hj2ks-kgs142tfsfhv0bvs8vvgjgs-afsvsbgtfs.com';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'uuid'.
     *
     * @group system
     */
    public function testTheUuidRule()
    {
        $input = ['uuid' => Str::uuid()];
        $rules = ['uuid' => 'uuid'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['uuid'] = 'jsjjs0vumajskjks';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'ascii'.
     *
     * @group system
     */
    public function testTheAsciiRule()
    {
        $rules = ['ascii' => 'ascii'];

        $this->assertTrue(Validator::make(['ascii' => 'testing'], $rules)->valid());
        $this->assertTrue(Validator::make(['ascii' => ''], $rules)->valid());
        $this->assertTrue(Validator::make(['ascii' => "a\nb\nc"], $rules)->valid());
        $this->assertTrue(Validator::make(['ascii' => "a\tb\tc"], $rules)->valid());

        $this->assertFalse(Validator::make(['ascii' => "tes\xe9ting"], $rules)->valid());
        $this->assertFalse(Validator::make(['ascii' => 'testiñg'], $rules)->valid());
    }

    /**
     * Test untuk rule 'image'.
     *
     * @group system
     */
    public function testTheImageRule()
    {
        $_FILES['photo']['tmp_name'] = path('storage').'test.png';
        $rules = ['photo' => 'image'];
        $this->assertTrue(Validator::make($_FILES, $rules)->valid());

        $_FILES['photo']['tmp_name'] = path('app').'routes.php';
        $this->assertFalse(Validator::make($_FILES, $rules)->valid());
    }

    /**
     * Test untuk rule 'alpha'.
     *
     * @group system
     */
    public function testTheAlphaRule()
    {
        $input = ['name' => 'BudiPurnomo'];
        $rules = ['name' => 'alpha'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['name'] = 'Budi Purnomo';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'alpha_num'.
     *
     * @group system
     */
    public function testTheAlphaNumRule()
    {
        $input = ['name' => 'BudiPurnomo1'];
        $rules = ['name' => 'alpha_num'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['name'] = 'Budi Purnomo';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'alpha_dash'.
     *
     * @group system
     */
    public function testTheAlphaDashRule()
    {
        $input = ['name' => 'Budi-Purnomo_1'];
        $rules = ['name' => 'alpha_dash'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['name'] = 'Budi Purnomo';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'mimes'.
     *
     * @group system
     */
    public function testTheMimesRule()
    {
        $_FILES['file']['tmp_name'] = path('app').'routes.php';
        $rules = ['file' => 'mimes:php,txt'];
        $this->assertTrue(Validator::make($_FILES, $rules)->valid());

        $rules = ['file' => 'mimes:jpg,bmp'];
        $this->assertFalse(Validator::make($_FILES, $rules)->valid());

        $_FILES['file']['tmp_name'] = path('storage').'test.png';
        $rules['file'] = 'mimes:png,bmp';
        $this->assertTrue(Validator::make($_FILES, $rules)->valid());

        $rules['file'] = 'mimes:txt,bmp';
        $this->assertFalse(Validator::make($_FILES, $rules)->valid());
    }

    /**
     * Test untuk rule 'unique'.
     *
     * @group system
     */
    public function testUniqueRule()
    {
        $rules = ['code' => 'unique:validation_unique'];

        $input = ['code' => 'KRW'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input = ['code' => 'JKT'];
        $this->assertFalse(Validator::make($input, $rules)->valid());

        $rules = ['code' => 'unique:validation_unique,code,JKT,code'];
        $this->assertTrue(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'exists'.
     *
     * @group system
     */
    public function testExistsRule()
    {
        $rules = ['code' => 'exists:validation_unique'];
        $input = ['code' => 'PWK'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $rules = ['code' => 'exists:validation_unique,code'];
        $input['code'] = ['PWK', 'BKS'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['code'] = ['PWK', 'KRW'];
        $this->assertFalse(Validator::make($input, $rules)->valid());

        $input['code'] = 'KRW';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test untuk rule 'date_format'.
     *
     * @group system
     */
    public function testTheDateFormatRule()
    {
        $input = ['date' => '15-Dec-2020'];
        $rules = ['date' => 'date_format:j-M-Y'];
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['date'] = '2020-12-15,12:01:43';
        $rules['date'] = 'date_format:"Y-m-d,H:i:s"';
        $this->assertTrue(Validator::make($input, $rules)->valid());

        $input['date'] = '2020-12-15';
        $this->assertFalse(Validator::make($input, $rules)->valid());

        $input['date'] = '12:01:43';
        $this->assertFalse(Validator::make($input, $rules)->valid());
    }

    /**
     * Test bahwa validator message bisa di-set dengan benar.
     *
     * @group system
     */
    public function testCorrectMessagesAreSet()
    {
        $lng = require path('app').'language'.DS.'en'.DS.'validation.php';

        $input = ['email' => 'example-foo'];
        $rules = ['name' => 'required', 'email' => 'required|email'];
        $v = Validator::make($input, $rules);
        $v->valid();

        $this->assertInstanceOf('\System\Messages', $v->errors);
        $this->assertEquals(str_replace(':attribute', 'name', $lng['required']), $v->errors->first('name'));
        $this->assertEquals(str_replace(':attribute', 'email', $lng['email']), $v->errors->first('email'));
    }

    /**
     * Test bahwa message kustom juga bisa digunakan.
     *
     * @group system
     */
    public function testCustomMessagesAreRecognize()
    {
        $messages = ['required' => 'Required!'];
        $rules = ['name' => 'required'];
        $v = Validator::make([], $rules, $messages);
        $v->valid();

        $this->assertEquals('Required!', $v->errors->first('name'));

        $messages['email_required'] = 'Email Required!';
        $rules = ['name' => 'required', 'email' => 'required'];
        $v = Validator::make([], $rules, $messages);
        $v->valid();

        $this->assertEquals('Required!', $v->errors->first('name'));
        $this->assertEquals('Email Required!', $v->errors->first('email'));

        $rules = ['custom' => 'required'];
        $v = Validator::make([], $rules);
        $v->valid();

        $this->assertEquals('The custom field is required.', $v->errors->first('custom'));
    }

    /**
     * Test bahwa placeholder ':attribute',':size', ':min' dan ':max'
     * pada rule size numerik bisa direplace dengan benar.
     *
     * @group system
     */
    public function testNumericSizeReplacementsAreMade()
    {
        $lng = require path('app').'language'.DS.'en'.DS.'validation.php';

        $input = ['amount' => 100];
        $rules = ['amount' => 'numeric|size:80'];
        $v = Validator::make($input, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':size'], ['amount', '80'], $lng['size']['numeric']);
        $this->assertEquals($expect, $v->errors->first('amount'));

        $rules = ['amount' => 'numeric|between:70,80'];
        $v = Validator::make($input, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':min', ':max'], ['amount', '70', '80'], $lng['between']['numeric']);
        $this->assertEquals($expect, $v->errors->first('amount'));

        $rules = ['amount' => 'numeric|min:120'];
        $v = Validator::make($input, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':min'], ['amount', '120'], $lng['min']['numeric']);
        $this->assertEquals($expect, $v->errors->first('amount'));

        $rules = ['amount' => 'numeric|max:20'];
        $v = Validator::make($input, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':max'], ['amount', '20'], $lng['max']['numeric']);
        $this->assertEquals($expect, $v->errors->first('amount'));
    }

    /**
     * Test bahwa placeholder ':attribute',':size', ':min' dan ':max'
     * pada rule size string bisa direplace dengan benar.
     *
     * @group system
     */
    public function testStringSizeReplacementsAreMade()
    {
        $lang = require path('app').'language'.DS.'en'.DS.'validation.php';

        $input = ['amount' => '100'];
        $rules = ['amount' => 'size:80'];
        $v = Validator::make($input, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':size'], ['amount', '80'], $lang['size']['string']);
        $this->assertEquals($expect, $v->errors->first('amount'));

        $rules = ['amount' => 'between:70,80'];
        $v = Validator::make($input, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':min', ':max'], ['amount', '70', '80'], $lang['between']['string']);
        $this->assertEquals($expect, $v->errors->first('amount'));

        $rules = ['amount' => 'min:120'];
        $v = Validator::make($input, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':min'], ['amount', '120'], $lang['min']['string']);
        $this->assertEquals($expect, $v->errors->first('amount'));

        $rules = ['amount' => 'max:2'];
        $v = Validator::make($input, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':max'], ['amount', '2'], $lang['max']['string']);
        $this->assertEquals($expect, $v->errors->first('amount'));
    }

    /**
     * Test bahwa placeholder ':attribute',':size', ':min' dan ':max'
     * pada rule size string bisa direplace dengan benar.
     *
     * @group system
     */
    public function testFileSizeReplacementsAreMade()
    {
        $lang = require path('app').'language'.DS.'en'.DS.'validation.php';

        $_FILES['amount']['tmp_name'] = 'foo';
        $_FILES['amount']['size'] = 10000;
        $rules = ['amount' => 'size:80'];
        $v = Validator::make($_FILES, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':size'], ['amount', '80'], $lang['size']['file']);
        $this->assertEquals($expect, $v->errors->first('amount'));

        $rules = ['amount' => 'between:70,80'];
        $v = Validator::make($_FILES, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':min', ':max'], ['amount', '70', '80'], $lang['between']['file']);
        $this->assertEquals($expect, $v->errors->first('amount'));

        $rules = ['amount' => 'min:120'];
        $v = Validator::make($_FILES, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':min'], ['amount', '120'], $lang['min']['file']);
        $this->assertEquals($expect, $v->errors->first('amount'));

        $rules = ['amount' => 'max:2'];
        $v = Validator::make($_FILES, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':max'], ['amount', '2'], $lang['max']['file']);
        $this->assertEquals($expect, $v->errors->first('amount'));
    }

    /**
     * Test bahwa placeholder ':values' bisa direplace dengan benar.
     *
     * @group system
     */
    public function testValuesGetReplaced()
    {
        $lang = require path('app').'language'.DS.'en'.DS.'validation.php';

        $_FILES['file']['tmp_name'] = path('storage').'test.png';
        $rules = ['file' => 'mimes:php,txt'];
        $v = Validator::make($_FILES, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':values'], ['file', 'php, txt'], $lang['mimes']);
        $this->assertEquals($expect, $v->errors->first('file'));
    }

    /**
     * Test bahwa nama atribut kustom dapat direplace dengan benar.
     *
     * @group system
     */
    public function testCustomAttributesAreReplaced()
    {
        $lang = require path('app').'language'.DS.'en'.DS.'validation.php';

        $rules = ['test_attribute' => 'required'];
        $v = Validator::make([], $rules);
        $v->valid();

        $expect = str_replace(':attribute', 'test attribute', $lang['required']);
        $this->assertEquals($expect, $v->errors->first('test_attribute'));
    }

    /**
     * Test bahwa nama atribut required_with direplace dengan benar.
     *
     * @group system
     */
    public function testRequiredWithAttributesAreReplaced()
    {
        $lang = require path('app').'language'.DS.'en'.DS.'validation.php';

        $data = ['first_name' => 'Budi', 'last_name' => ''];
        $rules = ['first_name' => 'required', 'last_name' => 'required_with:first_name'];

        $v = Validator::make($data, $rules);
        $v->valid();

        $expect = str_replace([':attribute', ':field'], ['last name', 'first name'], $lang['required_with']);
        $this->assertEquals($expect, $v->errors->first('last_name'));
    }
}
