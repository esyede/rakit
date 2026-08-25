<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Oops\Collectors;

/**
 * Covers the route matcher the debug bar uses to figure out which registered
 * route served the current request.
 */
class OopsCollectorsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Helper: reach the private matcher.
     *
     * @param string $pattern
     * @param string $uri
     *
     * @return bool
     */
    protected function match($pattern, $uri)
    {
        $method = new \ReflectionMethod('System\\Foundation\\Oops\\Collectors', 'matchRoute');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $method->setAccessible(true);
        $method->setAccessible(true);

        return $method->invoke(null, $pattern, $uri);
    }

    /**
     * @group system
     */
    public function testMatchesLiteralAndPlaceholderRoutes()
    {
        $this->assertTrue($this->match('/users', '/users'));
        $this->assertTrue($this->match('/users/(:num)', '/users/12'));
        $this->assertTrue($this->match('/docs/(:any)', '/docs/intro'));
        $this->assertTrue($this->match('/tag/(:alpha)', '/tag/php'));
        $this->assertTrue($this->match('/sku/(:alnum)', '/sku/ab12'));

        $this->assertFalse($this->match('/users/(:num)', '/users/budi'));
        $this->assertFalse($this->match('/tag/(:alpha)', '/tag/12'));
        $this->assertFalse($this->match('/users', '/users/12'));
    }

    /**
     * The route URI is literal text, so it has to be quoted before it goes into
     * a pattern. Unquoted, a '.' matched any character.
     *
     * @group system
     */
    public function testRegexMetacharactersInTheRouteAreTakenLiterally()
    {
        $this->assertTrue($this->match('/file.php', '/file.php'));
        $this->assertFalse($this->match('/file.php', '/fileXphp'));

        $this->assertTrue($this->match('/a+b', '/a+b'));
        $this->assertFalse($this->match('/a+b', '/aaab'));
    }

    /**
     * A '#' in the route used to collide with the pattern delimiter, breaking
     * the whole match.
     *
     * @group system
     */
    public function testDelimiterInTheRouteDoesNotBreakTheMatch()
    {
        $this->assertTrue($this->match('/a#(:num)', '/a#5'));
        $this->assertFalse($this->match('/a#(:num)', '/a#xy'));
    }
}
