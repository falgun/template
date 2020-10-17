<?php
declare(strict_types=1);

namespace Falgun\Template\Tests;

use PHPUnit\Framework\TestCase;
use Falgun\Template\Tests\Stubs\TemplateStub;
use Falgun\Template\AttributeNotFoundException;
use Falgun\Template\ViewFileNotFoundException;

final class TemplateTest extends TestCase
{

    protected TemplateStub $template;

    public function setUp(): void
    {
        $session = new \Falgun\Http\Session();
        $pagination = new \Falgun\Pagination\Pagination(1);

        $this->template = new TemplateStub($session, $pagination);
    }

    public function testTemplate()
    {
        $session = new \Falgun\Http\Session();
        $pagination = new \Falgun\Pagination\Pagination(1);

        $template = new TemplateStub($session, $pagination);


        $this->assertSame('', $template->getViewDir());
        $this->assertSame('', $template->getViewFile());
    }

    public function testInvalidViewFile()
    {
        $session = new \Falgun\Http\Session();
        $pagination = new \Falgun\Pagination\Pagination(1);

        $template = new TemplateStub($session, $pagination);

        try {
            $template->render();
            $this->fail('ViewFileNotFoundException should have been thrown');
        } catch (ViewFileNotFoundException $ex) {
            $this->assertSame('/ Not found', $ex->getMessage());
            $this->assertSame(500, $ex->getCode());
        }
    }

    public function testValidViewFile()
    {
        $session = new \Falgun\Http\Session();
        $pagination = new \Falgun\Pagination\Pagination(1);

        $template = new TemplateStub($session, $pagination);
        $template->view(__DIR__ . '/Stubs/Views/Unit/test');

        $template->setViewDir('/DIR');

        $this->assertSame(__DIR__ . '/Stubs/Views/Unit/test.php', $template->getViewFile());
        // default view dir is "/", so it will be prepended
        $this->assertSame('/DIR/' . __DIR__ . '/Stubs/Views/Unit/test.php', $template->getViewAbsolutePath());
    }

    public function testRender()
    {
        $this->template->view('test');

        $this->template->setViewDirFromControllerPath('\\App\\Controller\\UnitController', __DIR__ . '/Stubs/Views');

        $this->template->render();

        $this->assertSame(__DIR__ . '/Stubs/Views/Unit/test.php', $this->template->getViewAbsolutePath());
    }

    public function testBlankWithData()
    {
        $this->template->with();

        try {
            $this->template->undefined;
            $this->fail('AttributeNotFoundException should have been thrown');
        } catch (AttributeNotFoundException $ex) {
            $this->assertSame('"undefined" Not found in template! Have you passed the data from controller?', $ex->getMessage());
            $this->assertSame(500, $ex->getCode());
        }
    }

    public function testWithDataValue()
    {
        $this->template->with();

        $this->assertSame(false, $this->template->value('undefined'));
    }

    public function testValidWithData()
    {
        $this->template->with(['test' => 'data']);

        $this->assertSame('data', $this->template->test);
        $this->assertSame('data', $this->template->value('test'));
    }

    public function testObjectWithData()
    {
        $obj = (object) ['name' => 'falgun'];

        $this->template->with(['framework' => $obj]);

        $this->assertSame('falgun', $this->template->objProperty('framework', 'name'));
        $this->assertSame(false, $this->template->objProperty('framework', 'popularity'));
    }

    public function testMagic__SetData()
    {
        $this->template->test2 = 'data2';

        $this->assertSame('data2', $this->template->test2);
        $this->assertSame('data2', $this->template->value('test2'));
    }

    public function testMagic__isset()
    {
        $this->template->test2 = 'data2';

        $this->assertSame(true, isset($this->template->test2));
        $this->assertSame(false, isset($this->template->test3));
    }

    public function testMagic__call()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->template->invalidCall();
    }

    public function testEscapeHtml()
    {
        $this->assertSame('&lt;b&gt;html&lt;/b&gt;', $this->template->e('<b>html</b>'));
        $this->assertSame('&lt;b&gt;html&lt;/b&gt;', $this->template->escape('<b>html</b>'));
    }

    public function testEscapeJson()
    {
        $data = ['name' => '<b>falgun</b>'];

        $this->assertSame('{"name":"\u003Cb\u003Efalgun\u003C\/b\u003E"}', $this->template->escapeJson($data));
    }

    public function testInvalidPaginationState()
    {
        $template = new class extends \Falgun\Template\AbstractTemplate {

            public function postRender(): void
            {
                
            }

            public function preRender(): void
            {
                
            }
        };

        $this->assertTrue($template->pagination(new \Falgun\Pagination\Pagination(1)) instanceof \Falgun\Template\AbstractTemplate);
    }

    public function testInvalidSessionState()
    {
        $template = new class extends \Falgun\Template\AbstractTemplate {

            public function postRender(): void
            {
                
            }

            public function preRender(): void
            {
                
            }
        };

        try {
            $template->csrfToken();
            $this->fail('\RuntimeException should have been thrown');
        } catch (\InvalidArgumentException $ex) {
            $this->assertSame('You need to assign Session object in template class constructor', $ex->getMessage());
            $this->assertSame(500, $ex->getCode());
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidCsrfToken()
    {
        $_SESSION['undefined_csrf_token'] = 'abcd';

        try {
            $this->template->csrfToken();
            $this->fail('\RuntimeException should have been thrown');
        } catch (\RuntimeException $ex) {
            $this->assertSame('CSRF TOKEN is not found in session, have you loaded the middleware?', $ex->getMessage());
            $this->assertSame(403, $ex->getCode());
        }
    }

    public function testValidCsrfToken()
    {
        $_SESSION['falgun_csrf_token'] = 'abcd';

        $this->assertSame('<input type="hidden" value="abcd" name="csrf_token" />', $this->template->csrfToken());
    }

    public function testAbstraction()
    {
        $this->assertSame('', $this->template->getBody());
        $this->assertSame(200, $this->template->getStatusCode());
        $this->assertSame('OK', $this->template->getReasonPhrase());
    }

    public function testPrePostRender()
    {
        $this->template->view('test');

        $this->template->setViewDirFromControllerPath('\\App\\Controller\\UnitController', __DIR__ . '/Stubs/Views');

        $this->template->render();

        $this->assertSame(true, $this->template->loadedPreRender);
        $this->assertSame(true, $this->template->loadedPostRender);
    }

    public function testGetSetViewFolderNameFromController()
    {
        $this->template->setViewDirFromControllerPath('UnitController', '/parent/');

        $this->assertSame('/parent/Unit', $this->template->getViewDir());
    }

    public function testEscapeDefaultParams()
    {
        $this->assertSame('&lt;b&gt;html&lt;/b&gt;&quot;a&quot;', $this->template->e('<b>html</b>"a"'));
        $this->assertSame('&lt;b&gt;html&lt;/b&gt;&quot;a&quot;', $this->template->escape('<b>html</b>"a"'));

        $class = new Class {

            public function __toString()
            {
                return 'html@site.com';
            }
        };
        $this->assertSame('html@site.com', $this->template->escape($class));
    }
}
