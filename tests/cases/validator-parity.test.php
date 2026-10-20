<?php

defined('DS') or exit('No direct access.');

use System\Validator;

class ValidatorParityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * A closure rule fails through $fail, with ':attribute' replaced.
     *
     * @group system
     */
    public function testClosureRules()
    {
        $odd = function ($attribute, $value, $fail) {
            if (0 === $value % 2) {
                $fail('The :attribute must be odd.');
            }
        };

        $validation = Validator::make(['number' => 4], ['number' => ['required', 'integer', $odd]]);
        $this->assertTrue($validation->fails());
        $this->assertEquals('The number must be odd.', $validation->errors->first('number'));

        $this->assertTrue(Validator::make(['number' => 3], ['number' => ['integer', $odd]])->passes());

        // A lone closure, not wrapped in an array.
        $this->assertTrue(Validator::make(['number' => 4], ['number' => $odd])->fails());
    }

    /**
     * $fail() without a message uses the 'closure' language line, in the validator's
     * language; a dotted language key is translated, a sentence is kept as written.
     *
     * @group system
     */
    public function testClosureMessagesComeFromTheLanguageFiles()
    {
        $silent = function ($attribute, $value, $fail) {
            $fail();
        };

        $keyed = function ($attribute, $value, $fail) {
            $fail('validation.uuid');
        };

        $this->assertEquals(
            'The code is invalid.',
            ParityValidator::make(['code' => 'x'], ['code' => [$silent]])->first_error('code')
        );
        $this->assertEquals(
            'Bilah code tidak valid.',
            ParityValidator::make(['code' => 'x'], ['code' => [$silent]])->speaks('id')->first_error('code')
        );
        $this->assertEquals(
            'The code must be a valid UUID.',
            ParityValidator::make(['code' => 'x'], ['code' => [$keyed]])->first_error('code')
        );

        // A custom message for the attribute wins over the language line.
        $this->assertEquals(
            'Wrong code.',
            ParityValidator::make(['code' => 'x'], ['code' => [$silent]], ['code_closure' => 'Wrong code.'])->first_error('code')
        );
    }

    /**
     * Like any rule that is not implicit, a closure is skipped for an empty value.
     *
     * @group system
     */
    public function testClosureRulesSkipEmptyValues()
    {
        $called = false;
        $rule = function ($attribute, $value, $fail) use (&$called) {
            $called = true;
            $fail('never');
        };

        $this->assertTrue(Validator::make([], ['name' => [$rule]])->passes());
        $this->assertTrue(Validator::make(['name' => null], ['name' => ['nullable', $rule]])->passes());
        $this->assertFalse($called);
    }

    /**
     * Closure rules work on wildcard attributes, and next to the size rules.
     *
     * @group system
     */
    public function testClosureRulesOnWildcardsAndSizes()
    {
        $upper = function ($attribute, $value, $fail) {
            if (strtoupper($value) !== $value) {
                $fail(':attribute must be upper case.');
            }
        };

        $validation = Validator::make(
            ['codes' => ['AB', 'cd']],
            ['codes.*' => ['string', 'min:2', $upper]]
        );

        $this->assertTrue($validation->fails());
        $this->assertFalse($validation->errors->has('codes.0'));
        $this->assertTrue($validation->errors->has('codes.1'));
    }

    /**
     * bail stops an attribute at its first failure; other attributes still run.
     *
     * @group system
     */
    public function testBail()
    {
        $data = ['a' => 'x', 'b' => 'x'];
        $validation = Validator::make($data, ['a' => 'bail|integer|min:5', 'b' => 'integer|min:5']);

        $this->assertTrue($validation->fails());
        $this->assertCount(1, $validation->errors->get('a'));
        $this->assertCount(2, $validation->errors->get('b'));
    }

    /**
     * stop_on_first_failure() ends validation at the first attribute that fails.
     *
     * @group system
     */
    public function testStopOnFirstFailure()
    {
        $validation = Validator::make(['a' => 'x', 'b' => 'x'], ['a' => 'integer', 'b' => 'integer'])
            ->stop_on_first_failure();

        $this->assertTrue($validation->fails());
        $this->assertTrue($validation->errors->has('a'));
        $this->assertFalse($validation->errors->has('b'));
    }

    /**
     * sometimes validates an attribute only when it is present.
     *
     * @group system
     */
    public function testSometimes()
    {
        $rules = ['email' => 'sometimes|required|email'];

        $this->assertTrue(Validator::make([], $rules)->passes());
        $this->assertTrue(Validator::make(['email' => ''], $rules)->fails());
        $this->assertTrue(Validator::make(['email' => 'x'], $rules)->fails());
        $this->assertTrue(Validator::make(['email' => 'a@b.co'], $rules)->passes());
    }

    /**
     * The exclude rules skip validation and keep the attribute out of validated().
     *
     * @group system
     */
    public function testExcludeRules()
    {
        $data = ['type' => 'personal', 'company' => '', 'vat' => 'x', 'note' => 'n', 'phone' => '1'];
        $rules = [
            'type' => 'required|in:personal,business',
            'company' => 'exclude_if:type,personal|required',
            'vat' => 'exclude_unless:type,business|required|integer',
            'note' => 'exclude',
            'phone' => 'exclude_without:email|integer',
            'fax' => 'exclude_with:phone|required',
        ];

        $validation = Validator::make($data, $rules);

        $this->assertTrue($validation->passes());
        $this->assertEquals(['type' => 'personal'], $validation->validated());

        $data['type'] = 'business';
        $validation = Validator::make($data, $rules);

        $this->assertTrue($validation->fails());
        $this->assertTrue($validation->errors->has('company'));
        $this->assertTrue($validation->errors->has('vat'));
    }

    /**
     * An exclude rule without its parameters is refused.
     *
     * @group system
     */
    public function testExcludeIfNeedsParameters()
    {
        $this->setExpectedException('InvalidArgumentException');
        Validator::make(['a' => 1], ['a' => 'exclude_if'])->passes();
    }

    /**
     * validated() returns only the attributes with rules that are present, nested
     * as they came, and refuses to answer for invalid data.
     *
     * @group system
     */
    public function testValidated()
    {
        $data = [
            'name' => 'Budi',
            'is_admin' => 1,
            'address' => ['city' => 'Solo', 'secret' => 'x'],
            'tags' => [['id' => 1, 'extra' => 'x'], ['id' => 2]],
        ];

        $validation = Validator::make($data, [
            'name' => 'required',
            'address.city' => 'required',
            'tags.*.id' => 'integer',
            'missing' => 'nullable',
        ]);

        $this->assertEquals([
            'name' => 'Budi',
            'address' => ['city' => 'Solo'],
            'tags' => [['id' => 1], ['id' => 2]],
        ], $validation->validated());

        $this->assertEquals(['name' => 'Budi'], $validation->safe()->only(['name'])->all());
        $this->assertEquals(['name'], array_keys($validation->safe()->except(['address', 'tags'])->all()));

        $this->setExpectedException('RuntimeException');
        Validator::make([], ['name' => 'required'])->validated();
    }
}

class ParityValidator extends Validator
{
    public function first_error($attribute)
    {
        $this->fails();
        return $this->errors->first($attribute);
    }
}
