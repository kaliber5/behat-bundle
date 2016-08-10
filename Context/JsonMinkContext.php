<?php
namespace Kaliber5\BehatBundle\Context;

use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Driver\KernelDriver;
use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * Created by PhpStorm.
 * User: andreasschacht
 * Date: 22.04.16
 * Time: 11:43
 */

class JsonMinkContext extends MinkContext
{
    use PHPMatcherAssertions;

    protected $_defaultMimeTypes = array(
        'json' => 'application/json',
        'jsonapi' => 'application/vnd.api+json'
    );

    /**
     * @Then I should see a valid :responseType response
     */
    public function iShouldSeeAValidJsonResponse($responseType = 'json')
    {
        $this->assertResponseStatus(200);
        $this->assertSession()->responseHeaderContains('Content-Type', $this->getMimeType($responseType));
        $response = $this->getSession()->getPage()->getContent();
        if (json_decode($response) === null) {
            throw new \Exception('No valid JSON Response: '.$response);
        }

    }

    /**
     * @Then I should see a valid created :responseType response
     */
    public function iShouldSeeAValidCreatedResponse($responseType = 'json')
    {
        $this->assertResponseStatus(201);
        $this->assertSession()->responseHeaderContains('Content-Type', $this->getMimeType($responseType));
        $response = $this->getSession()->getPage()->getContent();
        if (json_decode($response) === null) {
            throw new \Exception('No valid JSON Response: '.$response);
        }

    }

    /**
     * @Then I should see a not found response
     */
    public function iShouldSeeANotFoundResponse()
    {
        $this->assertResponseStatus(404);
    }

    /**
     * @Then I should see a bad request response
     */
    public function iShouldSeeABadRequestResponse()
    {
        $this->assertResponseStatus(400);
    }

    /**
     * @Then I should see a method not allowed response
     */
    public function iShouldSeeAMethodNotAllowedResponse()
    {
        $this->assertResponseStatus(405);
    }

    /**
     * @Then I should see a unauthorized response
     */
    public function iShouldSeeUnauthorizedResponse()
    {
        $this->assertResponseStatus(401);
    }

    /**
     * @Then I should see a(n) access denied response
     */
    public function iShouldSeeAccessDeniedResponse()
    {
        $this->assertResponseStatus(403);
    }

    /**
     * @Then I should see a temporarily moved redirect response
     */
    public function iShouldSeeATemporarilyMovedRedirectResponse()
    {
        $this->assertResponseStatus(302);
    }

    /**
     * @Then I should see an empty response
     * @Then I should see a successful updated response
     * @Then I should see a successful deleted response
     */
    public function iShouldSeeAnEmptyResponse()
    {
        $this->assertResponseStatus(204);
    }



    /**
     * Send a DELETE-Request to a given url
     *
     * @param string $url         the url
     * @param string $sessionName optional, the mink session name
     */
    public function sendDeleteRequest($url, $sessionName = null)
    {
        $this->getSession($sessionName)->getDriver()->getClient()->request(
            'DELETE',
            $url
        );
    }

    /**
     * Send a POST-Request to a given url
     *
     * @param string $url
     * @param string $requestBodyFilename
     * @param string $requestType
     * @param array  $arguments
     * @param null   $sessionName
     * @param array  $replace
     */
    public function sendPostRequest($url, $requestBodyFilename, $requestType = 'json', array $arguments = array(), $sessionName = null, array $replace = [])
    {
        if ($requestBodyFilename !== null) {
            /** @noinspection PhpParamsInspection */
            $requestBodyFilename = $this->getJsonFileContent($this->getJsonRequestFilePath($requestBodyFilename), $replace);
        }

        $contentType = $this->getMimeType($requestType);
        $this->getSession($sessionName)->getDriver()->getClient()->request(
            'POST',
            $url,
            $arguments,
            [],
            [
                'CONTENT_TYPE' => $contentType,
                'HTTP_ACCEPT'  => $contentType,
            ],
            $requestBodyFilename
        );
    }

    /**
     * Send a PATCH-Request to a given url
     *
     * @param string $url                 the url
     * @param string $requestBodyFilename The filename that contains the Json-Request-Body
     * @param string $responseType        the response type
     * @param string $sessionName         optional, the mink session name
     * @param array  $replace             an array like [ 'searchval' => 'replaceval']
     *
     */
    public function sendPatchRequest(
        $url,
        $requestBodyFilename,
        $responseType = 'json',
        $sessionName = null,
        array $replace = []
    ) {
        $contentType = $this->getMimeType($responseType);
        /** @noinspection PhpParamsInspection */
        $this->getSession($sessionName)->getDriver()->getClient()->request(
            'PATCH',
            $url,
            [],
            [],
            [
                'CONTENT_TYPE' => $contentType,
                'HTTP_ACCEPT'  => $contentType,
            ],
            $this->getJsonFileContent($this->getJsonRequestFilePath($requestBodyFilename), $replace)
        );
    }

    /**
     * Visits an url without following a redirect response
     *
     * @param string $url
     */
    public function visitWithoutFollowRedirect($url)
    {
        $this->getSession()->getDriver()->getClient()->followRedirects(false);
        $this->visit($url);
        $this->getSession()->getDriver()->getClient()->followRedirects(true);
    }

    /**
     * returns the content of the current request
     *
     * @return string
     */
    public function getPageContent()
    {
        return $this->getSession()->getPage()->getContent();
    }

    /**
     * compares the content of a file to the response
     *
     * @param string $filename the filename
     * @param array  $replace  an array like [ 'searchval' => 'replaceval']
     *
     * @throws \Exception
     */
    public function compareJsonResponseToFileContent($filename, array $replace = [])
    {
        /** @noinspection PhpParamsInspection */
        $expected = $this->getReplacedFileContent($this->getJsonResponseFilePath($filename), $replace);
        $response = $this->getJsonResponseContent();
        assertThat($response, self::matchesPattern($expected), 'The value is not as expected');
    }

    /**
     * @Then I will see the :arg1 resources as json
     */
    public function iWillSeeTheListOfAsJson($arg1)
    {
        $expected = $this->getReplacedFileContent($this->getJsonResponseFilePath($arg1.'_get.json'));
        $response = $this->getJsonResponseContent();

        assertThat($response, self::matchesPattern($expected), 'The value is not as expected');
    }

    /**
     * @Then I will see the :arg1 resource with id :arg2 as json
     */
    public function iWillSeeTheResourceWithIdAsJson($arg1, $arg2)
    {
        $expected = $this->getReplacedFileContent($this->getJsonResponseFilePath($arg1.'_get_'.$arg2.'.json'));
        $response = $this->getJsonResponseContent();

        assertThat($response, self::matchesPattern($expected), 'The value is not as expected');
    }

    /**
     * returns the file content, replace the array-keys with the array values
     *
     * @param string $filename the filename
     * @param array  $replace  an array like [ 'searchval' => 'replaceval']
     *
     * @return mixed|string
     */
    protected function getReplacedFileContent($filename, array $replace = [])
    {
        $content = file_get_contents($filename);
        if (!empty($replace)) {
            $content = str_replace(array_keys($replace), array_values($replace), $content);
        }

        return $content;
    }

    /**
     * returns a well formatted json string from json file
     *
     * @param       $filename
     * @param array $replace an array like [ 'searchval' => 'replaceval']
     *
     * @return string
     */
    protected function getJsonFileContent($filename, array $replace = [])
    {
        $content = $this->getReplacedFileContent($filename, $replace);

        return $this->formatJsonString($content);
    }

    /**
     * returns a well formatted json string from response
     *
     * @return string
     */
    protected function getJsonResponseContent()
    {
        return $this->formatJsonString($this->getSession()->getPage()->getContent());
    }

    /**
     * returns an pretty formatted string
     *
     * @param $json
     * @return string
     */
    protected function formatJsonString($json)
    {
        return json_encode(json_decode($json, JSON_PRETTY_PRINT));
    }

    /**
     * returns the filepath with json response, default to currentdir/../JsonResponse/$file
     * @param string $file the filename
     * @return string
     */
    protected function getJsonResponseFilePath($file)
    {
        $reflection = new \ReflectionClass($this);
        $directory = dirname($reflection->getFileName()).PATH_SEPARATOR;

        return $directory.'/../JsonResponse/'.$file;
    }

    /**
     * returns the filepath with json response, default to currentdir/../JsonResponse/$file
     * @param string $file the filename
     * @return string
     */
    protected function getJsonRequestFilePath($file)
    {
        $reflection = new \ReflectionClass($this);
        $directory = dirname($reflection->getFileName()).PATH_SEPARATOR;

        return $directory.'/../JsonRequest/'.$file;
    }

    /**
     * @param string $responseType
     *
     * @return string
     */
    public function getMimeType($responseType)
    {
        return $this->_defaultMimeTypes[$responseType];
    }

    /**
     * @param array $defaultMimeTypes
     */
    public function setDefaultMimeTypes($defaultMimeTypes)
    {
        $this->_defaultMimeTypes = $defaultMimeTypes;
    }


    /**
     * @return Profile
     * @throws UnsupportedDriverActionException
     */
    public function getSymfonyProfile()
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof KernelDriver) {
            throw new UnsupportedDriverActionException(
                'You need to tag the scenario with '.'"@mink:symfony2". Using the profiler is not '.'supported by %s',
                $driver
            );
        }

        $profile = $driver->getClient()->getProfile();
        if (false === $profile) {
            throw new \RuntimeException(
                'The profiler is disabled. Activate it by setting '.'framework.profiler.only_exceptions to false in '.'your config'
            );
        }

        return $profile;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        /** @noinspection PhpUndefinedMethodInspection */

        return $this->getSession()->getDriver()->getClient()->getContainer();
    }
}
