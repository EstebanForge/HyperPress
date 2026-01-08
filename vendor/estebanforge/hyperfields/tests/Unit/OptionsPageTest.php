<?php

declare(strict_types=1);

namespace HyperFields\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use HyperFields\OptionsPage;
use HyperFields\OptionsSection;
use HyperFields\Field;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class OptionsPageTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    private OptionsPage $page;
    private $templateLoaderMock;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Stub WordPress functions
        Functions\stubTranslationFunctions();
        Functions\stubEscapeFunctions();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_file_name')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_url')->returnArg();
        Functions\when('wp_kses_post')->returnArg();
        Functions\when('add_query_arg')->justReturn('http://example.com/page');
        Functions\when('admin_url')->returnArg();
        // Functions\when('get_option')->justReturn([]); // Removed to allow specific expects to be primary
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_create_nonce')->justReturn('test_nonce');
        Functions\when('wp_hash')->justReturn('hash123');
        Functions\when('settings_fields')->justReturn('');
        Functions\when('do_settings_fields')->justReturn('');
        
        // Mock submit_button to echo output using alias
        Functions\when('submit_button')->alias(function($text, $type) {
            echo '<button>' . $text . '</button>';
        });

        $this->templateLoaderMock = \Mockery::mock('alias:HyperFields\TemplateLoader');

        // Use reflection to access private constructor
        $reflection = new \ReflectionClass(OptionsPage::class);
        $constructor = $reflection->getConstructor();
        $this->page = $reflection->newInstanceWithoutConstructor();
        $constructor->invoke($this->page, 'Test Page', 'test-page');
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testStaticMakeMethod()
    {
        $page = OptionsPage::make('Static Page', 'static-page');

        $this->assertInstanceOf(OptionsPage::class, $page);

        $reflection = new \ReflectionClass($page);
        $pageTitle = $reflection->getProperty('page_title');
        $this->assertEquals('Static Page', $pageTitle->getValue($page));
    }

    public function testPageCreation()
    {
        $reflection = new \ReflectionClass($this->page);

        $pageTitle = $reflection->getProperty('page_title');
        $this->assertEquals('Test Page', $pageTitle->getValue($this->page));

        $menuTitle = $reflection->getProperty('menu_title');
        $this->assertEquals('Test Page', $menuTitle->getValue($this->page));

        $menuSlug = $reflection->getProperty('menu_slug');
        $this->assertEquals('test-page', $menuSlug->getValue($this->page));

        $capability = $reflection->getProperty('capability');
        $this->assertEquals('manage_options', $capability->getValue($this->page));

        $parentSlug = $reflection->getProperty('parent_slug');
        $this->assertEquals('options-general.php', $parentSlug->getValue($this->page));
    }

    public function testSetMenuTitle()
    {
        $this->page->setMenuTitle('Custom Menu Title');

        $reflection = new \ReflectionClass($this->page);
        $menuTitle = $reflection->getProperty('menu_title');
        $this->assertEquals('Custom Menu Title', $menuTitle->getValue($this->page));
    }

    public function testSetCapability()
    {
        $this->page->setCapability('edit_posts');

        $reflection = new \ReflectionClass($this->page);
        $capability = $reflection->getProperty('capability');
        $this->assertEquals('edit_posts', $capability->getValue($this->page));
    }

    public function testSetParentSlug()
    {
        $this->page->setParentSlug('custom-parent');

        $reflection = new \ReflectionClass($this->page);
        $parentSlug = $reflection->getProperty('parent_slug');
        $this->assertEquals('custom-parent', $parentSlug->getValue($this->page));
    }

    public function testSetIconUrl()
    {
        $this->page->setIconUrl('dashicons-admin-tools');

        $reflection = new \ReflectionClass($this->page);
        $iconUrl = $reflection->getProperty('icon_url');
        $this->assertEquals('dashicons-admin-tools', $iconUrl->getValue($this->page));
    }

    public function testSetPosition()
    {
        $this->page->setPosition(25);

        $reflection = new \ReflectionClass($this->page);
        $position = $reflection->getProperty('position');
        $this->assertEquals(25, $position->getValue($this->page));
    }

    public function testSetOptionName()
    {
        $this->page->setOptionName('custom_options');

        $this->assertEquals('custom_options', $this->page->getOptionName());
    }

    public function testSetFooterContent()
    {
        $this->page->setFooterContent('<p>Footer content</p>');

        $reflection = new \ReflectionClass($this->page);
        $footerContent = $reflection->getProperty('footer_content');
        $this->assertEquals('<p>Footer content</p>', $footerContent->getValue($this->page));
    }

    public function testAddSection()
    {
        $section = $this->page->addSection('test_section', 'Test Section', 'Test description');

        $this->assertInstanceOf(OptionsSection::class, $section);
        $this->assertEquals('test_section', $section->getId());

        $reflection = new \ReflectionClass($this->page);
        $sections = $reflection->getProperty('sections');
        $sectionsArray = $sections->getValue($this->page);

        $this->assertArrayHasKey('test_section', $sectionsArray);
        $this->assertSame($section, $sectionsArray['test_section']);
    }

    public function testAddSectionObject()
    {
        $section = new OptionsSection('custom_section', 'Custom Section');
        $field = Field::make('text', 'test_field', 'Test Field')->setDefault('default_value');
        $section->addField($field);

        $result = $this->page->addSectionObject($section);

        $this->assertSame($this->page, $result);

        $reflection = new \ReflectionClass($this->page);
        $sections = $reflection->getProperty('sections');
        $sectionsArray = $sections->getValue($this->page);
        $this->assertArrayHasKey('custom_section', $sectionsArray);

        $defaultValues = $reflection->getProperty('default_values');
        $defaultValuesArray = $defaultValues->getValue($this->page);
        $this->assertArrayHasKey('test_field', $defaultValuesArray);
        $this->assertEquals('default_value', $defaultValuesArray['test_field']);
    }

    public function testAddField()
    {
        $field = Field::make('text', 'test_field', 'Test Field');

        $result = $this->page->addField($field);

        $this->assertSame($this->page, $result);

        $reflection = new \ReflectionClass($this->page);
        $fields = $reflection->getProperty('fields');
        $fieldsArray = $fields->getValue($this->page);
        $this->assertArrayHasKey('test_field', $fieldsArray);
    }

    public function testRegister()
    {
        // For implicit call to loadOptions()
        Functions\expect('get_option')
            ->once()
            ->with('hyperpress_options', [])
            ->andReturn([]);

        Functions\expect('add_action')->once()->with('admin_menu', \Mockery::type('callable'));
        Functions\expect('add_action')->once()->with('admin_init', \Mockery::type('callable'));
        Functions\expect('add_action')->once()->with('admin_enqueue_scripts', \Mockery::type('callable'));

        $this->page->register();
    }

    public function testLoadOptions()
    {
        $this->page->setOptionName('custom_option_name');

        // Setup a section with a field to populate default_values
        $section = new OptionsSection('section1', 'Section 1');
        $field1 = Field::make('text', 'field1', 'Field 1')->setDefault('default1');
        $field2 = Field::make('text', 'field2', 'Field 2')->setDefault('default2');
        $section->addField($field1);
        $section->addField($field2);
        $this->page->addSectionObject($section);

        $reflection = new \ReflectionClass($this->page);
        $method = $reflection->getMethod('loadOptions');
        $optionValuesProp = $reflection->getProperty('option_values');

        // Scenario 1: get_option returns saved values that override defaults
        Functions\expect('get_option')
            ->once()
            ->with('custom_option_name', [])
            ->andReturn(['field1' => 'saved1', 'field3' => 'saved3']);

        $method->invoke($this->page);
        $values = $optionValuesProp->getValue($this->page);
        $this->assertEquals('saved1', $values['field1']);
        $this->assertEquals('default2', $values['field2']); // Default from field2
        $this->assertEquals('saved3', $values['field3']); // New saved field

        // Scenario 2: get_option returns empty array (only defaults are used)
        Functions\expect('get_option')
            ->once()
            ->with('custom_option_name', [])
            ->andReturn([]);
        
        $method->invoke($this->page);
        $values = $optionValuesProp->getValue($this->page);
        $this->assertEquals('default1', $values['field1']);
        $this->assertEquals('default2', $values['field2']);
        $this->assertArrayNotHasKey('field3', $values);

        // Scenario 3: get_option returns empty array (simulating false, but compatible with array_merge)
        Functions\expect('get_option')
            ->once()
            ->with('custom_option_name', [])
            ->andReturn([]);
        
        $method->invoke($this->page);
        $values = $optionValuesProp->getValue($this->page);
        $this->assertEquals('default1', $values['field1']);
        $this->assertEquals('default2', $values['field2']);
    }

    public function testAddMenuPageWithParent()
    {
        $this->page->setParentSlug('options-general.php');

        Functions\expect('add_submenu_page')
            ->once()
            ->with(
                'options-general.php',
                'Test Page',
                'Test Page',
                'manage_options',
                'test-page',
                [$this->page, 'renderPage'],
                null
            );

        $this->page->addMenuPage();
    }

    public function testAddMenuPageAsTopLevel()
    {
        $this->page->setParentSlug('menu');

        Functions\expect('add_menu_page')
            ->once()
            ->with(
                'Test Page',
                'Test Page',
                'manage_options',
                'test-page',
                [$this->page, 'renderPage'],
                '',
                null
            );

        $this->page->addMenuPage();
    }

    public function testRegisterSettings()
    {
        $section = $this->page->addSection('test_section', 'Test Section');
        $field = Field::make('text', 'test_field', 'Test Field');
        $section->addField($field);

        Functions\expect('register_setting')
            ->once()
            ->with(
                'hyperpress_options',
                'hyperpress_options',
                ['sanitize_callback' => [$this->page, 'sanitizeOptions']]
            );

        Functions\expect('add_settings_section')
            ->once()
            ->with('test_section', '', '__return_false', 'hyperpress_options');

        Functions\expect('add_settings_field')
            ->once()
            ->with('test_field', '', [$field, 'render'], 'hyperpress_options', 'test_section', $field->getArgs());

        $this->page->registerSettings();
    }

    public function testSanitizeOptions()
    {
        $section = $this->page->addSection('test_section', 'Test Section');
        $textField = Field::make('text', 'text_field', 'Text Field');
        $checkboxField = Field::make('checkbox', 'checkbox_field', 'Checkbox Field');
        $section->addField($textField);
        $section->addField($checkboxField);

        $_POST['hyperpress_active_tab'] = 'test_section';
        $input = ['text_field' => 'sanitized text'];

        $result = $this->page->sanitizeOptions($input);

        $this->assertArrayHasKey('text_field', $result);
        $this->assertEquals('sanitized text', $result['text_field']);
        $this->assertArrayHasKey('checkbox_field', $result);
        $this->assertEquals('0', $result['checkbox_field']); // Unchecked checkbox
    }

    public function testSanitizeOptionsNonCheckboxNotPresent()
    {
        $section = $this->page->addSection('test_section', 'Test Section');
        $textField = Field::make('text', 'text_field', 'Text Field');
        $nonCheckboxField = Field::make('text', 'non_checkbox_field', 'Non Checkbox Field'); // A non-checkbox field
        $section->addField($textField);
        $section->addField($nonCheckboxField);

        // Pre-load option_values to simulate existing saved values
        $reflection = new \ReflectionClass($this->page);
        $optionValues = $reflection->getProperty('option_values');
        $optionValues->setValue($this->page, ['non_checkbox_field' => 'existing_value']);

        $_POST['hyperpress_active_tab'] = 'test_section';
        $input = ['text_field' => 'sanitized text']; // non_checkbox_field is not in input

        $result = $this->page->sanitizeOptions($input);

        $this->assertArrayHasKey('non_checkbox_field', $result);
        $this->assertEquals('existing_value', $result['non_checkbox_field']); // Should preserve existing value
    }


    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testSanitizeOptionsInvalidCompactInput()
    {
        if (!defined('HYPERPRESS_COMPACT_INPUT')) {
            define('HYPERPRESS_COMPACT_INPUT', true);
        }

        $_POST['hyperpress_compact_input'] = '{"hyperpress_options": "not_an_array"}'; // Invalid format
        $_POST['hyperpress_active_tab'] = 'test_section';

        $section = $this->page->addSection('test_section', 'Test Section');
        $section->addField(Field::make('text', 'text_field', 'Text Field'));

        $result = $this->page->sanitizeOptions([]); // Pass empty input, it should be replaced by compact input

        // Should return original empty options (or defaults if defined), as compact input was invalid
        $this->assertEquals([], $result);
    }

    public function testRegisterSettingsEmptySection()
    {
        $section = new OptionsSection('empty_section', 'Empty Section Title');
        $this->page->addSectionObject($section); // Add a section with no fields

        Functions\expect('register_setting')
            ->once()
            ->with(
                'hyperpress_options',
                'hyperpress_options',
                ['sanitize_callback' => [$this->page, 'sanitizeOptions']]
            );
        
        Functions\expect('add_settings_section')
            ->once()
            ->with('empty_section', '', '__return_false', 'hyperpress_options');
        
        Functions\expect('add_settings_field')->never(); // Should not be called if section has no fields

        $this->page->registerSettings();
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testRegisterSettingsNoSections()
    {
        // Ensure no sections are added to the page
        $reflection = new \ReflectionClass($this->page);
        $sectionsProperty = $reflection->getProperty('sections');
        $sectionsProperty->setValue($this->page, []); // Explicitly empty sections

        Functions\expect('register_setting')
            ->once()
            ->with(
                'hyperpress_options',
                'hyperpress_options',
                ['sanitize_callback' => [$this->page, 'sanitizeOptions']]
            );
        
        // Ensure no add_settings_section or add_settings_field are called
        Functions\expect('add_settings_section')->never();
        Functions\expect('add_settings_field')->never();

        $this->page->registerSettings();
    }

    public function testGetActiveTabFromPost()
    {
        $this->page->addSection('section1', 'Section 1');
        $this->page->addSection('section2', 'Section 2');

        $_POST['hyperpress_active_tab'] = 'section2';

        $reflection = new \ReflectionClass($this->page);
        $method = $reflection->getMethod('getActiveTab');

        $result = $method->invoke($this->page);

        $this->assertEquals('section2', $result);
    }

    public function testGetActiveTabFromGet()
    {
        $this->page->addSection('section1', 'Section 1');
        $this->page->addSection('section2', 'Section 2');

        $_GET['tab'] = 'section1';

        $reflection = new \ReflectionClass($this->page);
        $method = $reflection->getMethod('getActiveTab');

        $result = $method->invoke($this->page);

        $this->assertEquals('section1', $result);
    }

    public function testGetActiveTabDefault()
    {
        $this->page->addSection('section1', 'Section 1');
        $this->page->addSection('section2', 'Section 2');

        $reflection = new \ReflectionClass($this->page);
        $method = $reflection->getMethod('getActiveTab');

        $result = $method->invoke($this->page);

        $this->assertEquals('section1', $result);
    }

    public function testGetActiveTabNoSections()
    {
        $reflection = new \ReflectionClass($this->page);
        $method = $reflection->getMethod('getActiveTab');

        $result = $method->invoke($this->page);

        $this->assertEquals('main', $result);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testEnqueueAssets()
    {
        if (!defined('HYPERPRESS_PLUGIN_URL')) {
            define('HYPERPRESS_PLUGIN_URL', 'http://example.com/plugin/');
        }
        if (!defined('HYPERPRESS_VERSION')) {
            define('HYPERPRESS_VERSION', '2.0.7');
        }

        $this->templateLoaderMock->shouldReceive('enqueueAssets')->once();

        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'hyperpress-admin-options',
                'http://example.com/plugin/assets/js/admin-options.js',
                ['jquery'],
                '2.0.7',
                true
            );

        Functions\expect('wp_localize_script')
            ->once()
            ->with(
                'hyperpress-admin-options',
                'hyperpressOptions',
                \Mockery::type('array')
            );

        $this->page->enqueueAssets('settings_page_test-page');
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testEnqueueAssetsWrongPage()
    {
        $this->templateLoaderMock->shouldNotReceive('enqueueAssets');

        $this->page->enqueueAssets('wrong_page');
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testEnqueueAssetsNoPluginUrl()
    {
        // Ensure HYPERPRESS_PLUGIN_URL is NOT defined
        // PreserveGlobalState(false) already handles this if it's run in separate process

        $this->templateLoaderMock->shouldReceive('enqueueAssets')->once(); // This is still called

        Functions\expect('wp_enqueue_script')->never();
        Functions\expect('wp_localize_script')->never();

        $this->page->enqueueAssets('settings_page_test-page');
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testEnqueueAssetsSubmenuPage()
    {
        if (!defined('HYPERPRESS_PLUGIN_URL')) {
            define('HYPERPRESS_PLUGIN_URL', 'http://example.com/plugin/');
        }
        if (!defined('HYPERPRESS_VERSION')) {
            define('HYPERPRESS_VERSION', '2.0.7');
        }

        $this->page->setParentSlug('custom-parent'); // Custom parent slug
        
        $this->templateLoaderMock->shouldReceive('enqueueAssets')->once();

        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'hyperpress-admin-options',
                'http://example.com/plugin/assets/js/admin-options.js',
                ['jquery'],
                '2.0.7',
                true
            );

        Functions\expect('wp_localize_script')
            ->once()
            ->with(
                'hyperpress-admin-options',
                'hyperpressOptions',
                \Mockery::type('array')
            );

        $this->page->enqueueAssets('custom-parent_page_test-page'); // The expected hook_suffix
    }

    public function testRenderPage()
    {
        Functions\when('esc_attr')->returnArg();
        $this->page->addSection('test_section', 'Test Section', 'Test Description');

        // Stub settings fields to avoid errors
        Functions\when('settings_fields')->justReturn('');
        Functions\when('do_settings_fields')->justReturn('');
        Functions\when('submit_button')->alias(function() {
            echo '<button>Custom Submit</button>';
        });

        ob_start();
        $this->page->renderPage();
        $output = ob_get_clean();

        $this->assertStringContainsString('wrap', $output);
        $this->assertStringContainsString('Test Page', $output);
        $this->assertStringContainsString('nav-tab-wrapper', $output);
        $this->assertStringContainsString('Test Section', $output);
        $this->assertStringContainsString('Test Description', $output);
        $this->assertStringContainsString('<button>Custom Submit</button>', $output); // Assert custom string
    }

    public function testRenderPageWithFooter()
    {
        Functions\when('esc_attr')->returnArg();
        $this->page->setFooterContent('<p>Custom footer</p>');
        $this->page->addSection('main_section', 'Main Section');

        Functions\when('settings_fields')->justReturn('');
        Functions\when('do_settings_fields')->justReturn('');
        Functions\when('submit_button')->alias(function() {
            echo '<button>Custom Submit</button>';
        });

        ob_start();
        $this->page->renderPage();
        $output = ob_get_clean();

        $this->assertStringContainsString('Custom footer', $output);
        $this->assertStringContainsString('hyperpress-options-footer', $output);
        $this->assertStringContainsString('<button>Custom Submit</button>', $output); // Assert custom string
    }

    public function testRenderTabsNoSections()
    {
        $reflection = new \ReflectionClass($this->page);
        $method = $reflection->getMethod('renderTabs');

        ob_start();
        $method->invoke($this->page);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testRenderPageNoSections()
    {
        // Ensure no sections are added to the page
        $reflection = new \ReflectionClass($this->page);
        $sectionsProperty = $reflection->getProperty('sections');
        $sectionsProperty->setValue($this->page, []);

        Functions\when('esc_attr')->returnArg();
        Functions\when('settings_fields')->justReturn('');
        Functions\when('do_settings_fields')->justReturn('');
        Functions\when('submit_button')->alias(function() {
            echo '<button>Custom Submit</button>';
        });

        ob_start();
        $this->page->renderPage();
        $output = ob_get_clean();

        $this->assertStringContainsString('wrap', $output); // Check outer wrapper
        $this->assertStringContainsString('Test Page', $output); // Check title
        $this->assertStringNotContainsString('nav-tab-wrapper', $output); // No tabs if no sections
        $this->assertStringContainsString('<button>Custom Submit</button>', $output); // Submit button always present
    }

    public function testFluentInterface()
    {
        $result = $this->page->setMenuTitle('Custom Title')
                           ->setCapability('edit_posts')
                           ->setParentSlug('custom-parent')
                           ->setIconUrl('dashicon')
                           ->setPosition(25);

        $this->assertSame($this->page, $result);
    }
}