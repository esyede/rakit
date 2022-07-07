# CURL

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#pengetahuan-dasar)
- [Creating Request](#membuat-request)
- [Custom Options](#opsi-kustom)
- [Processing Response](#mengolah-response)
    - [Response Header](#response-header)
    - [Response Body](#response-body)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Basic Knowledge

Curl is a type of command that commonly used in Unix-based systems. Actually,
the term stands for "Client URL".

The uses of this command include checking connectivity to URLs and transferring data.
In addition, this type of command can be used in various protocols. Curl is also equipped
with [libcurl](https://curl.haxx.se/libcurl), a URL transfer library that works on the client side.


> Don't forget to activate the [PHP Curl](http://php.net/manual/en/book.curl.php)
  extension on your server if it doesn't already activated.


<a id="membuat-request"></a>
## Creating Request

Rakit has provided several functionalities that you can use to work with Curl.
Like when you want to retrieve data via third-party APIs, download files and others.

Now, let's try to make a simple request using this component:


#### Creating GET request

To make a request of type `GET`, please use the `get()` method like this:

```php
$response = Curl::get('https://reqres.in/api/users?page=2');
```


#### Creating POST request

To make a request of type `POST`, please use the `post()` method like this:

```php
$parameters = ['name' =>  'Danang', 'age' => 25];

$response = Curl::post('https://reqres.in/api/users', $parameters);
```


#### Creating PUT request

To make a request of type `PUT`, please use the `put()` method like this:

```php
$parameters = ['name' =>  'Agus', 'age' => 24];

$response = Curl::put('https://reqres.in/api/users', $parameters);
```


#### Creating DELETE request

To make a request of type `DELETE`, please use the `delete()` method like this:

```php
$parameters = ['id' => 6];

$response = Curl::delete('https://reqres.in/api/users', $parameters);
```

In addition to using the specific method above, you can also make a request via the
`request()` method like this:


```php
$response = Curl::request($method = 'get', $url, $params, $options);
```


#### Downloading file

You can also download files using this component. It's also very easy:

```php
$target = 'https://github.com/esyede/rakit/archive/master.zip';
$destination = path('storage').'rakit.zip';

if (Curl::download($target, $destination)) {
   // Yay! downloaded successfuly!
}
```


<a id="opsi-kustom"></a>
## Custom Options

In the real life, everyone of course needs to send a curl request with different configuration
from one another. For that, we have provided an option set function for accommodate these needs.

```php
$parameters =[];
$custom_options = [
   CURLOPT_FOLLOWLOCATION => true,
   CURLOPT_HTTPHEADER => [
      'Cache-Control: no-cache',
      'Accept-Encoding: gzip, deflate',
      'Accept-Language: en-US,en;q=0.5',
   ],
];

$response = Curl::get('https://foobar.com', $parameters, $custom_options);
```

You can read a complete list of cURL options at
[official documentation](https://www.php.net/manual/en/function.curl-setopt.php).



<a id="mengolah-response"></a>
## Processing Response

After the request is executed, this component will return a `stdClass` object
containing the response from the request you made.
This response can then be processed for your application needs.



<a id="response-header"></a>
### Response Header

```php
dd($response->header);
```

<a id="response-body"></a>
### Response Body

```php
dd($response->body);
```
