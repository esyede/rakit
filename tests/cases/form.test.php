<?php

defined('DS') or exit('No direct script access.');

use System\Request;
use System\Foundation\Http\Request as FoundationRequest;

class FormTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        URL::$base = null;
        Config::set('application.url', 'http://localhost');
        Config::set('application.index', 'index.php');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Config::set('application.url', '');
        Config::set('application.index', 'index.php');
    }

    /**
     * Test untuk method Form::open().
     *
     * @group system
     */
    public function testOpeningForm()
    {
        $form1 = Form::open('z', 'GET');
        $form2 = Form::open('z', 'POST');
        $form3 = Form::open('z', 'PUT', ['accept-charset' => 'UTF-16', 'class' => 'form']);
        $form4 = Form::open('z', 'DELETE', ['class' => 'form']);

        $out1 = '<form method="GET" action="http://localhost/index.php/z" accept-charset="UTF-8">';
        $out2 = '<form method="POST" action="http://localhost/index.php/z" accept-charset="UTF-8">';

        $out3 = '<form accept-charset="UTF-16" class="form" method="POST" '.
            'action="http://localhost/index.php/z"><input type="hidden" name="_method" value="PUT">';

        $out4 = '<form class="form" method="POST" '.
            'action="http://localhost/index.php/z" accept-charset="UTF-8">'.
            '<input type="hidden" name="_method" value="DELETE">';

        $this->assertEquals($out1, $form1);
        $this->assertEquals($out2, $form2);
        $this->assertEquals($out3, $form3);
        $this->assertEquals($out4, $form4);
    }

    /**
     * Test untuk method Form::open() - 2 (https).
     *
     * @group system
     */
    public function testOpeningFormSecure()
    {
        $this->setServerVar('HTTPS', 'on');
        $form1 = Form::open('z', 'GET');
        $form2 = Form::open('z', 'POST');
        $form3 = Form::open('z', 'PUT', ['accept-charset' => 'UTF-16', 'class' => 'form']);
        $form4 = Form::open('z', 'DELETE', ['class' => 'form']);
        $this->setServerVar('HTTPS', 'off');

        $out1 = '<form method="GET" action="https://localhost/index.php/z" accept-charset="UTF-8">';
        $out2 = '<form method="POST" action="https://localhost/index.php/z" accept-charset="UTF-8">';

        $out3 = '<form accept-charset="UTF-16" class="form" method="POST" '.
            'action="https://localhost/index.php/z"><input type="hidden" name="_method" value="PUT">';

        $out4 = '<form class="form" method="POST" action="https://localhost/index.php/z" '.
            'accept-charset="UTF-8"><input type="hidden" name="_method" value="DELETE">';

        $this->assertEquals($out1, $form1);
        $this->assertEquals($out2, $form2);
        $this->assertEquals($out3, $form3);
        $this->assertEquals($out4, $form4);
    }

    /**
     * Test untuk method Form::open_for_files().
     *
     * @group system
     */
    public function testOpeningFormForFile()
    {
        $form1 = Form::open_for_files('z', 'GET');
        $form2 = Form::open_for_files('z', 'POST');
        $form3 = Form::open_for_files('z', 'PUT', ['accept-charset' => 'UTF-16', 'class' => 'form']);
        $form4 = Form::open_for_files('z', 'DELETE', ['class' => 'form']);

        $out1 = '<form enctype="multipart/form-data" method="GET" '.
            'action="http://localhost/index.php/z" accept-charset="UTF-8">';

        $out2 = '<form enctype="multipart/form-data" method="POST" '.
            'action="http://localhost/index.php/z" accept-charset="UTF-8">';

        $out3 = '<form accept-charset="UTF-16" class="form" enctype="multipart/form-data" '.
            'method="POST" action="http://localhost/index.php/z">'.
            '<input type="hidden" name="_method" value="PUT">';

        $out4 = '<form class="form" enctype="multipart/form-data" method="POST" '.
            'action="http://localhost/index.php/z" accept-charset="UTF-8">'.
            '<input type="hidden" name="_method" value="DELETE">';

        $this->assertEquals($out1, $form1);
        $this->assertEquals($out2, $form2);
        $this->assertEquals($out3, $form3);
        $this->assertEquals($out4, $form4);
    }

    /**
     * Test untuk method Form::open_for_files() - 2 (https).
     *
     * @group system
     */
    public function testOpeningFormSecureForFile()
    {
        $this->setServerVar('HTTPS', 'on');
        $form1 = Form::open_for_files('z', 'GET');
        $form2 = Form::open_for_files('z', 'POST');
        $form3 = Form::open_for_files('z', 'PUT', ['accept-charset' => 'UTF-16', 'class' => 'form']);
        $form4 = Form::open_for_files('z', 'DELETE', ['class' => 'form']);
        $this->setServerVar('HTTPS', 'off');

        $out1 = '<form enctype="multipart/form-data" method="GET" '.
            'action="https://localhost/index.php/z" accept-charset="UTF-8">';

        $out2 = '<form enctype="multipart/form-data" method="POST" '.
            'action="https://localhost/index.php/z" accept-charset="UTF-8">';

        $out3 = '<form accept-charset="UTF-16" class="form" enctype="multipart/form-data" '.
            'method="POST" action="https://localhost/index.php/z">'.
            '<input type="hidden" name="_method" value="PUT">';

        $out4 = '<form class="form" enctype="multipart/form-data" method="POST" '.
            'action="https://localhost/index.php/z" accept-charset="UTF-8">'.
            '<input type="hidden" name="_method" value="DELETE">';

        $this->assertEquals($out1, $form1);
        $this->assertEquals($out2, $form2);
        $this->assertEquals($out3, $form3);
        $this->assertEquals($out4, $form4);
    }

    /**
     * Test untuk method Form::close().
     *
     * @group system
     */
    public function testClosingForm()
    {
        $this->assertEquals('</form>', Form::close());
    }

    /**
     * Test untuk method Form::label().
     *
     * @group system
     */
    public function testFormLabel()
    {
        $form1 = Form::label('y', 'b');
        $form2 = Form::label('y', 'b', ['class' => 'control-label']);

        $this->assertEquals('<label for="y">b</label>', $form1);
        $this->assertEquals('<label for="y" class="control-label">b</label>', $form2);
    }

    /**
     * Test untuk method Form::input().
     *
     * @group system
     */
    public function testFormInput()
    {
        $form1 = Form::input('text', 'y');
        $form2 = Form::input('text', 'y', 'z');
        $form3 = Form::input('date', 'z', null, ['class' => 'x']);

        $this->assertEquals('<input type="text" name="y" id="y">', $form1);
        $this->assertEquals('<input type="text" name="y" value="z" id="y">', $form2);
        $this->assertEquals('<input class="x" type="date" name="z">', $form3);
    }

    /**
     * Test untuk method Form::text().
     *
     * @group system
     */
    public function testFormText()
    {
        $form1 = Form::input('text', 'y');
        $form2 = Form::text('y');
        $form3 = Form::text('y', 'z');
        $form4 = Form::text('y', null, ['class' => 'x']);

        $this->assertEquals('<input type="text" name="y" id="y">', $form1);
        $this->assertEquals($form1, $form2);
        $this->assertEquals('<input type="text" name="y" value="z" id="y">', $form3);
        $this->assertEquals('<input class="x" type="text" name="y" id="y">', $form4);
    }

    /**
     * Test untuk method Form::password().
     *
     * @group system
     */
    public function testFormPassword()
    {
        $form1 = Form::input('password', 'y');
        $form2 = Form::password('y');
        $form3 = Form::password('y', ['class' => 'x']);

        $this->assertEquals('<input type="password" name="y" id="y">', $form1);
        $this->assertEquals($form1, $form2);
        $this->assertEquals('<input class="x" type="password" name="y" id="y">', $form3);
    }

    /**
     * Test untuk method Form::hidden().
     *
     * @group system
     */
    public function testFormHidden()
    {
        $form1 = Form::input('hidden', 'y');
        $form2 = Form::hidden('y');
        $form3 = Form::hidden('y', 'z');
        $form4 = Form::hidden('y', null, ['class' => 'x']);

        $this->assertEquals('<input type="hidden" name="y" id="y">', $form1);
        $this->assertEquals($form1, $form2);
        $this->assertEquals('<input type="hidden" name="y" value="z" id="y">', $form3);
        $this->assertEquals('<input class="x" type="hidden" name="y" id="y">', $form4);
    }

    /**
     * Test untuk method Form::search().
     *
     * @group system
     */
    public function testFormSearch()
    {
        $form1 = Form::input('search', 'y');
        $form2 = Form::search('y');
        $form3 = Form::search('y', 'z');
        $form4 = Form::search('y', null, ['class' => 'x']);

        $this->assertEquals('<input type="search" name="y" id="y">', $form1);
        $this->assertEquals($form1, $form2);
        $this->assertEquals('<input type="search" name="y" value="z" id="y">', $form3);
        $this->assertEquals('<input class="x" type="search" name="y" id="y">', $form4);
    }

    /**
     * Test untuk method Form::email().
     *
     * @group system
     */
    public function testFormEmail()
    {
        $form1 = Form::input('email', 'y');
        $form2 = Form::email('y');
        $form3 = Form::email('y', 'z');
        $form4 = Form::email('y', null, ['class' => 'x']);

        $this->assertEquals('<input type="email" name="y" id="y">', $form1);
        $this->assertEquals($form1, $form2);
        $this->assertEquals('<input type="email" name="y" value="z" id="y">', $form3);
        $this->assertEquals('<input class="x" type="email" name="y" id="y">', $form4);
    }

    /**
     * Test untuk method Form::telephone().
     *
     * @group system
     */
    public function testFormTelephone()
    {
        $form1 = Form::input('tel', 'y');
        $form2 = Form::telephone('y');
        $form3 = Form::telephone('y', 'z');
        $form4 = Form::telephone('y', null, ['class' => 'x']);

        $this->assertEquals('<input type="tel" name="y" id="y">', $form1);
        $this->assertEquals($form1, $form2);
        $this->assertEquals('<input type="tel" name="y" value="z" id="y">', $form3);
        $this->assertEquals('<input class="x" type="tel" name="y" id="y">', $form4);
    }

    /**
     * Test untuk method Form::url().
     *
     * @group system
     */
    public function testFormUrl()
    {
        $form1 = Form::input('url', 'y');
        $form2 = Form::url('y');
        $form3 = Form::url('y', 'z');
        $form4 = Form::url('y', null, ['class' => 'x']);

        $this->assertEquals('<input type="url" name="y" id="y">', $form1);
        $this->assertEquals($form1, $form2);
        $this->assertEquals('<input type="url" name="y" value="z" id="y">', $form3);
        $this->assertEquals('<input class="x" type="url" name="y" id="y">', $form4);
    }

    /**
     * Test untuk method Form::number().
     *
     * @group system
     */
    public function testFormNumber()
    {
        $form1 = Form::input('number', 'y');
        $form2 = Form::number('y');
        $form3 = Form::number('y', 'z');
        $form4 = Form::number('y', null, ['class' => 'x']);

        $this->assertEquals('<input type="number" name="y" id="y">', $form1);
        $this->assertEquals($form1, $form2);
        $this->assertEquals('<input type="number" name="y" value="z" id="y">', $form3);
        $this->assertEquals('<input class="x" type="number" name="y" id="y">', $form4);
    }

    /**
     * Test untuk method Form::date().
     *
     * @group system
     */
    public function testFormDate()
    {
        $form1 = Form::input('date', 'y');
        $form2 = Form::date('y');
        $form3 = Form::date('y', 'z');
        $form4 = Form::date('y', null, ['class' => 'x']);

        $this->assertEquals('<input type="date" name="y" id="y">', $form1);
        $this->assertEquals($form1, $form2);
        $this->assertEquals('<input type="date" name="y" value="z" id="y">', $form3);
        $this->assertEquals('<input class="x" type="date" name="y" id="y">', $form4);
    }

    /**
     * Test untuk method Form::file().
     *
     * @group system
     */
    public function testFormFile()
    {
        $form1 = Form::input('file', 'y');
        $form2 = Form::file('y');
        $form3 = Form::file('y', ['class' => 'x']);

        $this->assertEquals('<input type="file" name="y" id="y">', $form1);
        $this->assertEquals($form1, $form2);
        $this->assertEquals('<input class="x" type="file" name="y" id="y">', $form3);
    }

    /**
     * Test untuk method Form::textarea().
     *
     * @group system
     */
    public function testFormTextarea()
    {
        $form1 = Form::textarea('y');
        $form2 = Form::textarea('y', 'z');
        $form3 = Form::textarea('y', null, ['class' => 'x']);

        $this->assertEquals('<textarea name="y" id="y" rows="10" cols="50"></textarea>', $form1);
        $this->assertEquals('<textarea name="y" id="y" rows="10" cols="50">z</textarea>', $form2);
        $this->assertEquals('<textarea class="x" name="y" id="y" rows="10" cols="50"></textarea>', $form3);
    }

    /**
     * Test untuk method Form::select().
     *
     * @group system
     */
    public function testFormSelect()
    {
        $form1 = Form::select('y');
        $form2 = Form::select('y', ['z' => 'b', 'hello' => 'Hello World'], 'z');
        $form3 = Form::select('y', ['z' => 'b', 'hello' => 'Hello World'], null, ['class' => 'x']);
        $form4 = Form::select('y', ['y' => ['z' => 'b'], 'hello' => 'Hello World'], 'z');

        $out1 = '<select id="y" name="y"></select>';

        $out2 = '<select id="y" name="y"><option value="z" selected="selected">b</option>'.
            '<option value="hello">Hello World</option></select>';

        $out3 = '<select class="x" id="y" name="y"><option value="z">b</option>'.
            '<option value="hello">Hello World</option></select>';

        $out4 = '<select id="y" name="y"><optgroup label="y">'.
            '<option value="z" selected="selected">b</option></optgroup>'.
            '<option value="hello">Hello World</option></select>';

        $this->assertEquals($out1, $form1);
        $this->assertEquals($out2, $form2);
        $this->assertEquals($out3, $form3);
        $this->assertEquals($out4, $form4);
    }

    /**
     * Test untuk method Form::checkbox().
     *
     * @group system
     */
    public function testFormCheckbox()
    {
        $form1 = Form::input('checkbox', 'y');
        $form2 = Form::checkbox('y');
        $form3 = Form::checkbox('y', 'w', true);
        $form4 = Form::checkbox('y', 'w', false, ['class' => 'x']);

        $this->assertEquals('<input type="checkbox" name="y" id="y">', $form1);
        $this->assertEquals('<input id="y" type="checkbox" name="y" value="1">', $form2);
        $this->assertEquals('<input checked="checked" id="y" type="checkbox" name="y" value="w">', $form3);
        $this->assertEquals('<input class="x" id="y" type="checkbox" name="y" value="w">', $form4);
    }

    /**
     * Test untuk method Form::radio().
     *
     * @group system
     */
    public function testFormRadio()
    {
        $form1 = Form::input('radio', 'y');
        $form2 = Form::radio('y');
        $form3 = Form::radio('y', 'w', true);
        $form4 = Form::radio('y', 'w', false, ['class' => 'x']);

        $this->assertEquals('<input type="radio" name="y" id="y">', $form1);
        $this->assertEquals('<input id="y" type="radio" name="y" value="y">', $form2);
        $this->assertEquals('<input checked="checked" id="y" type="radio" name="y" value="w">', $form3);
        $this->assertEquals('<input class="x" id="y" type="radio" name="y" value="w">', $form4);
    }

    /**
     * Test untuk method Form::submit().
     *
     * @group system
     */
    public function testFormSubmit()
    {
        $form1 = Form::submit('y');
        $form2 = Form::submit('y', ['class' => 'x']);

        $this->assertEquals('<input type="submit" value="y">', $form1);
        $this->assertEquals('<input class="x" type="submit" value="y">', $form2);
    }

    /**
     * Test untuk method Form::reset().
     *
     * @group system
     */
    public function testFormReset()
    {
        $form1 = Form::reset('y');
        $form2 = Form::reset('y', ['class' => 'x']);

        $this->assertEquals('<input type="reset" value="y">', $form1);
        $this->assertEquals('<input class="x" type="reset" value="y">', $form2);
    }

    /**
     * Test untuk method Form::image().
     *
     * @group system
     */
    public function testFormImage()
    {
        $form1 = Form::image('y/w.png', 'y');
        $form2 = Form::image('y/w.png', 'y', ['class' => 'x']);
        $form3 = Form::image('https://site.com/z', 'z');

        $out1 = '<input src="http://localhost/assets/y/w.png" type="image" name="y" id="y">';
        $out2 = '<input class="x" src="http://localhost/assets/y/w.png" type="image" name="y" id="y">';
        $out3 = '<input src="https://site.com/z" type="image" name="z">';

        $this->assertEquals($out1, $form1);
        $this->assertEquals($out2, $form2);
        $this->assertEquals($out3, $form3);
    }

    /**
     * Test untuk method Form::button().
     *
     * @group system
     */
    public function testFormButton()
    {
        $form1 = Form::button('y');
        $form2 = Form::button('y', ['class' => 'x']);

        $this->assertEquals('<button>y</button>', $form1);
        $this->assertEquals('<button class="x">y</button>', $form2);
    }

    /**
     * Helper: set variabel $_SERVER.
     *
     * @param string $key
     * @param mixed  $value
     */
    protected function setServerVar($key, $value)
    {
        $_SERVER[$key] = $value;

        $this->restartRequest();
    }

    /**
     * Inisialisasi ulang global request.
     *
     * @return void
     */
    protected function restartRequest()
    {
        $_FILES = [];

        Request::$foundation = FoundationRequest::createFromGlobals();
    }
}
