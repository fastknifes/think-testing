<?php
// +----------------------------------------------------------------------
// | InteractsWithPages
// +----------------------------------------------------------------------
// | Copyright (c) 2019 http://www.shuipf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 水平凡 <admin@abc3210.com>
// +----------------------------------------------------------------------

namespace think\testing;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\ExpectationFailedException as PHPUnitException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use think\facade\Request;
use think\facade\Route;
use think\File;
use think\helper\Str;
use think\response\Redirect;

trait InteractsWithPages
{

    /**
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $crawler;

    /**
     * All of the stored inputs for the current page.
     *
     * @var array
     */
    protected $inputs = [];

    /**
     * All of the stored uploads for the current page.
     *
     * @var array
     */
    protected $uploads = [];

    /**
     * @param $uri
     * @return InteractsWithPages
     */
    public function visit($uri)
    {
        return $this->makeRequest('GET', $uri);
    }

    /**
     * @param $buttonText
     * @param array $inputs
     * @param array $uploads
     * @return $this
     */
    protected function submitForm($buttonText, $inputs = [], $uploads = [])
    {
        $this->makeRequestUsingForm($this->fillForm($buttonText, $inputs), $uploads);

        return $this;
    }

    /**
     * @param $text
     * @param bool $negate
     * @return $this
     */
    protected function see($text, $negate = false)
    {
        $method = $negate ? 'assertNotRegExp' : 'assertRegExp';
        $rawPattern = preg_quote($text, '/');
        $escapedPattern = preg_quote(htmlentities($text, ENT_QUOTES, 'UTF-8', false), '/');
        $pattern = $rawPattern == $escapedPattern
            ? $rawPattern : "({$rawPattern}|{$escapedPattern})";
        $this->$method("/$pattern/i", $this->response->getContent());
        return $this;
    }

    /**
     * @param $text
     * @return InteractsWithPages
     */
    protected function notSee($text)
    {
        return $this->see($text, true);
    }

    /**
     * @param $element
     * @param $text
     * @param bool $negate
     * @return $this|InteractsWithPages
     */
    public function seeInElement($element, $text, $negate = false)
    {
        if ($negate) {
            return $this->notSeeInElement($element, $text);
        }
        $this->assertTrue(
            $this->hasInElement($element, $text),
            "Element [$element] should contain the expected text [{$text}]"
        );
        return $this;
    }

    /**
     * @param $element
     * @param $text
     * @return $this
     */
    public function notSeeInElement($element, $text)
    {
        $this->assertFalse(
            $this->hasInElement($element, $text),
            "Element [$element] should not contain the expected text [{$text}]"
        );
        return $this;
    }

    /**
     * @param $text
     * @param null $url
     * @return $this
     */
    public function seeLink($text, $url = null)
    {
        $message = "No links were found with expected text [{$text}]";
        if ($url) {
            $message .= " and URL [{$url}]";
        }
        $this->assertTrue($this->hasLink($text, $url), "{$message}.");
        return $this;
    }

    /**
     * @param $text
     * @param null $url
     * @return $this
     */
    public function notSeeLink($text, $url = null)
    {
        $message = "A link was found with expected text [{$text}]";
        if ($url) {
            $message .= " and URL [{$url}]";
        }
        $this->assertFalse($this->hasLink($text, $url), "{$message}.");
        return $this;
    }

    /**
     * @param $text
     * @param null $url
     * @return bool
     */
    protected function hasLink($text, $url = null)
    {
        $links = $this->crawler->selectLink($text);
        if ($links->count() == 0) {
            return false;
        }
        // If the URL is null, we assume the developer only wants to find a link
        // with the given text regardless of the URL. So, if we find the link
        // we will return true now. Otherwise, we look for the given URL.
        if (null == $url) {
            return true;
        }
        $url = $this->addRootToRelativeUrl($url);
        /**
         * @var \DOMElement $link
         */
        foreach ($links as $link) {
            if ($link->getAttribute('href') == $url) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $url
     * @return string
     */
    protected function addRootToRelativeUrl($url)
    {
        if (!Str::startsWith($url, ['http', 'https'])) {
            return Route::buildUrl($url)->build();
        }
        return $url;
    }

    /**
     * @param $selector
     * @param $expected
     * @return $this
     * @throws Exception
     */
    public function seeInField($selector, $expected)
    {
        $this->assertSame(
            $expected, $this->getInputOrTextAreaValue($selector),
            "The field [{$selector}] does not contain the expected value [{$expected}]."
        );
        return $this;
    }

    /**
     * @param $selector
     * @param $value
     * @return $this
     * @throws Exception
     */
    public function notSeeInField($selector, $value)
    {
        $this->assertNotSame(
            $this->getInputOrTextAreaValue($selector), $value,
            "The input [{$selector}] should not contain the value [{$value}]."
        );
        return $this;
    }

    /**
     * @param $selector
     * @return $this
     * @throws Exception
     */
    public function seeIsChecked($selector)
    {
        $this->assertTrue(
            $this->isChecked($selector),
            "The checkbox [{$selector}] is not checked."
        );
        return $this;
    }

    /**
     * @param $selector
     * @return $this
     * @throws Exception
     */
    public function notSeeIsChecked($selector)
    {
        $this->assertFalse(
            $this->isChecked($selector),
            "The checkbox [{$selector}] is checked."
        );
        return $this;
    }

    /**
     * @param $selector
     * @return bool
     * @throws Exception
     */
    protected function isChecked($selector)
    {
        $checkbox = $this->filterByNameOrId($selector, "input[type='checkbox']");

        if ($checkbox->count() == 0) {
            throw new Exception("There are no checkbox elements with the name or ID [$selector].");
        }
        return $checkbox->attr('checked') !== null;
    }

    /**
     * @param $selector
     * @param $expected
     * @return $this
     * @throws Exception
     */
    public function seeIsSelected($selector, $expected)
    {
        $this->assertEquals(
            $expected, $this->getSelectedValue($selector),
            "The field [{$selector}] does not contain the selected value [{$expected}]."
        );
        return $this;
    }

    /**
     * @param $selector
     * @param $value
     * @return $this
     * @throws Exception
     */
    public function notSeeIsSelected($selector, $value)
    {
        $this->assertNotEquals(
            $value, $this->getSelectedValue($selector),
            "The field [{$selector}] contains the selected value [{$value}]."
        );
        return $this;
    }

    /**
     * @param $selector
     * @return string
     * @throws Exception
     */
    protected function getSelectedValue($selector)
    {
        $field = $this->filterByNameOrId($selector);
        if ($field->count() == 0) {
            throw new Exception("There are no elements with the name or ID [$selector].");
        }
        $element = $field->nodeName();
        if ('select' == $element) {
            return $this->getSelectedValueFromSelect($field);
        }
        if ('input' == $element) {
            return $this->getCheckedValueFromRadioGroup($field);
        }
        throw new Exception("Given selector [$selector] is not a select or radio group.");
    }

    /**
     * @param Crawler $field
     * @return string
     * @throws Exception
     */
    protected function getSelectedValueFromSelect(Crawler $field)
    {
        if ($field->nodeName() !== 'select') {
            throw new Exception('Given element is not a select element.');
        }
        /**
         * @var \DOMElement $option
         */
        foreach ($field->children() as $option) {
            if ($option->hasAttribute('selected')) {
                return $option->getAttribute('value');
            }
        }
    }

    /**
     * @param Crawler $radioGroup
     * @return string
     * @throws Exception
     */
    protected function getCheckedValueFromRadioGroup(Crawler $radioGroup)
    {
        if ($radioGroup->nodeName() !== 'input' || $radioGroup->attr('type') !== 'radio') {
            throw new Exception('Given element is not a radio button.');
        }
        /** @var \DOMElement $radio */
        foreach ($radioGroup as $radio) {
            if ($radio->hasAttribute('checked')) {
                return $radio->getAttribute('value');
            }
        }
    }

    /**
     * @param $name
     * @return $this
     */
    protected function click($name)
    {
        $link = $this->crawler->selectLink($name);
        if (!count($link)) {
            $link = $this->filterByNameOrId($name, 'a');
            if (!count($link)) {
                throw new InvalidArgumentException(
                    "Could not find a link with a body, name, or ID attribute of [{$name}]."
                );
            }
        }
        $this->visit($link->link()->getUri());
        return $this;
    }

    /**
     * @param $text
     * @param $element
     * @return InteractsWithPages
     */
    protected function type($text, $element)
    {
        return $this->storeInput($element, $text);
    }

    /**
     * @param $element
     * @return InteractsWithPages
     */
    protected function check($element)
    {
        return $this->storeInput($element, true);
    }

    /**
     * @param $element
     * @return InteractsWithPages
     */
    protected function uncheck($element)
    {
        return $this->storeInput($element, false);
    }

    /**
     * @param $option
     * @param $element
     * @return InteractsWithPages
     */
    protected function select($option, $element)
    {
        return $this->storeInput($element, $option);
    }

    /**
     * @param $absolutePath
     * @param $element
     * @return InteractsWithPages
     */
    protected function attach($absolutePath, $element)
    {
        $this->uploads[$element] = $absolutePath;

        return $this->storeInput($element, $absolutePath);
    }

    /**
     * @param $buttonText
     * @return InteractsWithPages
     */
    protected function press($buttonText)
    {
        return $this->submitForm($buttonText, $this->inputs, $this->uploads);
    }

    /**
     * @param $selector
     * @return string|null
     * @throws Exception
     */
    protected function getInputOrTextAreaValue($selector)
    {
        $field = $this->filterByNameOrId($selector, ['input', 'textarea']);
        if ($field->count() == 0) {
            throw new Exception("There are no elements with the name or ID [$selector].");
        }
        $element = $field->nodeName();
        if ('input' == $element) {
            return $field->attr('value');
        }
        if ('textarea' == $element) {
            return $field->text();
        }
        throw new Exception("Given selector [$selector] is not an input or textarea.");
    }

    /**
     * @param $uri
     * @return $this
     */
    protected function seePageIs($uri)
    {
        $this->assertPageLoaded($uri = $this->prepareUrlForRequest($uri));
        $this->assertEquals(
            $uri, $this->currentUri, "Did not land on expected page [{$uri}].\n"
        );
        return $this;
    }

    /**
     * @param $element
     * @param $text
     * @return bool
     */
    protected function hasInElement($element, $text)
    {
        $elements = $this->crawler->filter($element);
        $rawPattern = preg_quote($text, '/');
        $escapedPattern = preg_quote(htmlentities($text, ENT_QUOTES, 'UTF-8', false), '/');
        $pattern = $rawPattern == $escapedPattern
            ? $rawPattern : "({$rawPattern}|{$escapedPattern})";
        foreach ($elements as $element) {
            $element = new Crawler($element);
            if (preg_match("/$pattern/i", $element->html())) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $element
     * @param $text
     * @return $this
     */
    protected function storeInput($element, $text)
    {
        $this->assertFilterProducesResults($element);
        $element = str_replace('#', '', $element);
        $this->inputs[$element] = $text;
        return $this;
    }

    /**
     * @param $filter
     */
    protected function assertFilterProducesResults($filter)
    {
        $crawler = $this->filterByNameOrId($filter);
        if (!count($crawler)) {
            throw new InvalidArgumentException(
                "Nothing matched the filter [{$filter}] CSS query provided for [{$this->currentUri}]."
            );
        }
    }

    /**
     * @param $buttonText
     * @param array $inputs
     * @return Form
     */
    protected function fillForm($buttonText, $inputs = [])
    {
        if (!is_string($buttonText)) {
            $inputs = $buttonText;
            $buttonText = null;
        }
        return $this->getForm($buttonText)->setValues($inputs);
    }

    /**
     * @param null $buttonText
     * @return Form
     */
    protected function getForm($buttonText = null)
    {
        try {
            if ($buttonText) {
                return $this->crawler->selectButton($buttonText)->form();
            }
            return $this->crawler->filter('form')->form();
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                "Could not find a form that has submit button [{$buttonText}]."
            );
        }
    }

    /**
     * @param $name
     * @param string $elements
     * @return Crawler
     */
    protected function filterByNameOrId($name, $elements = '*')
    {
        $name = str_replace('#', '', $name);
        $id = str_replace(['[', ']'], ['\\[', '\\]'], $name);
        $elements = is_array($elements) ? $elements : [$elements];
        array_walk(
            $elements,
            function (&$element) use ($name, $id) {
                $element = "{$element}#{$id}, {$element}[name='{$name}']";
            }
        );
        return $this->crawler->filter(implode(', ', $elements));
    }

    /**
     * @param $method
     * @param $uri
     * @param array $parameters
     * @param array $cookies
     * @param array $files
     * @return $this
     */
    protected function makeRequest($method, $uri, $parameters = [], $cookies = [], $files = [])
    {
        $uri = $this->prepareUrlForRequest($uri);
        $this->call($method, $uri, $parameters, $cookies, $files);
        $this->clearInputs()->followRedirects()->assertPageLoaded($uri);
        $this->currentUri = Request::url(true);
        $this->crawler = new Crawler($this->response->getContent(), $this->currentUri);
        return $this;
    }

    /**
     * @param Form $form
     * @param array $uploads
     * @return InteractsWithPages
     */
    protected function makeRequestUsingForm(Form $form, array $uploads = [])
    {
        $files = $this->convertUploadsForTesting($form, $uploads);

        return $this->makeRequest(
            $form->getMethod(), $form->getUri(), $this->extractParametersFromForm($form), [], $files
        );
    }

    /**
     * @param $uri
     * @param null $message
     */
    protected function assertPageLoaded($uri, $message = null)
    {
        $status = $this->response->getCode();
        try {
            $this->assertEquals(200, $status);
        } catch (PHPUnitException $e) {
            $message = $message ?: "A request to [{$uri}] failed. Received status code [{$status}].";
            throw new HttpException($message);
        }
    }

    /**
     * @param Form $form
     * @param array $uploads
     * @return array|false
     */
    protected function convertUploadsForTesting(Form $form, array $uploads)
    {
        $files = $form->getFiles();
        $names = array_keys($files);
        $files = array_map(
            function (array $file, $name) use ($uploads) {
                return isset($uploads[$name])
                    ? $this->getUploadedFileForTesting($file, $uploads, $name)
                    : $file;
            }, $files, $names
        );
        return array_combine($names, $files);
    }

    /**
     * @param $file
     * @param $uploads
     * @param $name
     * @return File
     */
    protected function getUploadedFileForTesting($file, $uploads, $name)
    {
        $file['name'] = basename($uploads[$name]);
        return new File($file['tmp_name'], true);
    }

    /**
     * @param Form $form
     * @return mixed
     */
    protected function extractParametersFromForm(Form $form)
    {
        parse_str(http_build_query($form->getValues()), $parameters);
        return $parameters;
    }

    /**
     * 清空Input内容
     * @return $this
     */
    protected function clearInputs()
    {
        $this->inputs = [];
        $this->uploads = [];
        return $this;
    }

    /**
     * @return $this
     */
    protected function followRedirects()
    {
        while ($this->response instanceof Redirect) {
            $this->makeRequest('GET', $this->response->getTargetUrl());
        }
        return $this;
    }

    /**
     * 请求地址处理
     * @param $uri
     * @return string
     */
    protected function prepareUrlForRequest($uri)
    {
        //检查是否以/开头
        if (Str::startsWith($uri, '/')) {
            $uri = substr($uri, 1);
        }
        //检查是否以http开头
        if (!Str::startsWith($uri, 'http')) {
            $uri = $this->baseUrl . '/' . $uri;
        }
        return $uri;
    }
}
