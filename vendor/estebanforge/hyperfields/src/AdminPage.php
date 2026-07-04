<?php

declare(strict_types=1);

namespace HyperFields;

/**
 * Non-form admin page host.
 *
 * Emits the same page chrome as OptionsPage (sticky white header with H1,
 * URL-based nav tabs, hyperpress-notice-catcher so WP notices relocate below
 * the header) but WITHOUT the settings form: no <form>, no settings_fields(),
 * no Save button, no register_setting(), no sanitize. Each tab's body is an
 * arbitrary render callback.
 *
 * Back-compat: additive only. OptionsPage, TabsField, templates, CSS and JS
 * are untouched. The sticky-header and notice-relocation JS key off DOM
 * anchors (.hyperpress-options-wrap, [data-hyperpress-sticky-header],
 * #hyperpress-layout__notice-catcher) that this class emits identically.
 */
class AdminPage
{
    private string $page_title;
    private string $menu_title;
    private string $capability;
    private string $menu_slug;
    private string $parent_slug;
    private string $icon_url;
    private ?int $position;
    /**
     * @var array<string, array{title: string, render: callable}>
     */
    private array $tabs = [];
    private ?string $footer_content = null;

    /**
     * Make.
     *
     * @return self
     */
    public static function make(string $page_title, string $menu_slug): self
    {
        return new self($page_title, $menu_slug);
    }

    /**
     * Construct.
     */
    private function __construct(string $page_title, string $menu_slug)
    {
        $this->page_title = $page_title;
        $this->menu_title = $page_title;
        $this->menu_slug = $menu_slug;
        $this->capability = 'manage_options';
        $this->parent_slug = 'options-general.php';
        $this->icon_url = '';
        $this->position = null;
    }

    /**
     * SetMenuTitle.
     *
     * @return self
     */
    public function setMenuTitle(string $menu_title): self
    {
        $this->menu_title = $menu_title;

        return $this;
    }

    /**
     * SetCapability.
     *
     * @return self
     */
    public function setCapability(string $capability): self
    {
        $this->capability = $capability;

        return $this;
    }

    /**
     * SetParentSlug. Use 'menu' for a top-level menu page, or any parent slug
     * (e.g. 'tools.php', 'options-general.php') for a submenu page.
     *
     * @return self
     */
    public function setParentSlug(string $parent_slug): self
    {
        $this->parent_slug = $parent_slug;

        return $this;
    }

    /**
     * SetIconUrl.
     *
     * @return self
     */
    public function setIconUrl(string $icon_url): self
    {
        $this->icon_url = $icon_url;

        return $this;
    }

    /**
     * SetPosition.
     *
     * @return self
     */
    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * SetFooterContent. Optional HTML rendered after the page content.
     *
     * @return self
     */
    public function setFooterContent(string $footer_content): self
    {
        $this->footer_content = $footer_content;

        return $this;
    }

    /**
     * AddTab. Registers a URL-based nav tab whose body is an arbitrary render
     * callback. Tabs render always once at least one is added; active tab is
     * read from the ?tab= query param (falls back to the first tab).
     *
     * @param callable $render Render callback for the tab body. Receives no args.
     *
     * @return self
     */
    public function addTab(string $id, string $title, callable $render): self
    {
        if (!isset($this->tabs[$id])) {
            $this->tabs[$id] = [
                'title' => $title,
                'render' => $render,
            ];
        }

        return $this;
    }

    /**
     * Register. Hooks menu + asset enqueues.
     *
     * @return void
     */
    public function register(): void
    {
        if (doing_filter('admin_menu')) {
            $this->addMenuPage();
        } else {
            add_action('admin_menu', $this->addMenuPage(...));
        }

        add_action('admin_enqueue_scripts', $this->enqueueAssets(...));
    }

    /**
     * AddMenuPage.
     *
     * @return void
     */
    public function addMenuPage(): void
    {
        if ($this->parent_slug === 'menu') {
            add_menu_page(
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->menu_slug,
                [$this, 'renderPage'],
                $this->icon_url,
                $this->position
            );
        } else {
            add_submenu_page(
                $this->parent_slug,
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->menu_slug,
                [$this, 'renderPage'],
                $this->position
            );
        }
    }

    /**
     * RenderPage.
     *
     * @return void
     */
    public function renderPage(): void
    {
        $active_tab = $this->getActiveTab();
        ?>
        <div class="wrap hyperpress hyperpress-options-wrap" id="hyperpress-options-page">
            <div class="hyperpress-layout__header" data-hyperpress-sticky-header>
                <div class="hyperpress-layout__header-wrapper">
                    <h1 class="hyperpress-layout__header-heading"><?php echo esc_html($this->page_title); ?></h1>
                </div>
            </div>
            <?php $this->renderTabs(); ?>
            <?php
            // WP drops notices after the first .wp-header-end, and the sticky
            // header JS relocates matching notices to sit just before this
            // catcher so they render below the header, above the content.
            //
            // The catcher is wrapped in a non-.wrap container on purpose: the
            // notice-hiding inline style (opacity:0 !important) targets
            // '.wrap.hyperpress-options-wrap > .notice'. After the JS moves a
            // notice to the catcher's parent, that parent must NOT be
            // .hyperpress-options-wrap or the hiding rule keeps matching and
            // the notice stays invisible. OptionsPage gets this isolation for
            // free from its <form>; AdminPage has no form, so we add an
            // explicit wrapper.
            echo '<div class="hyperpress-notice-region">';
            echo '<div class="wp-header-end hyperpress-notice-catcher" id="hyperpress-layout__notice-catcher"></div>';
            echo '</div>';

            if (isset($this->tabs[$active_tab]) && is_callable($this->tabs[$active_tab]['render'])) {
                echo '<div class="hyperpress-page-content">';
                ($this->tabs[$active_tab]['render'])();
                echo '</div>';
            }

            if ($this->footer_content !== null && $this->footer_content !== '') {
                echo '<div class="hyperpress-options-footer">';
                echo wp_kses_post($this->footer_content);
                echo '</div>';
            }

            echo '</div>';
    }

    /**
     * GetActiveTab. Reads ?tab= when it matches a registered tab, otherwise the
     * first registered tab (or 'main' if none registered).
     *
     * @return string
     */
    private function getActiveTab(): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
        if ($tab !== '' && isset($this->tabs[$tab])) {
            return $tab;
        }

        $tab_keys = array_keys($this->tabs);

        return $tab_keys[0] ?? 'main';
    }

    /**
     * RenderTabs.
     *
     * @return void
     */
    private function renderTabs(): void
    {
        if (empty($this->tabs)) {
            return;
        }

        $active_tab = $this->getActiveTab();
        echo '<nav class="nav-tab-wrapper hyperpress-nav-tab-wrapper" aria-label="' . esc_attr__('Page sections', 'api-for-htmx') . '">';
        foreach ($this->tabs as $tab_id => $tab) {
            $class = 'nav-tab hyperpress-nav-tab';
            if ($active_tab === $tab_id) {
                $class .= ' nav-tab-active';
            }
            $url_base = $this->parent_slug === 'options-general.php' ? 'options-general.php' : 'admin.php';
            $url = add_query_arg(['page' => $this->menu_slug, 'tab' => $tab_id], admin_url($url_base));
            $aria_current = $active_tab === $tab_id ? ' aria-current="page"' : '';
            echo '<a href="' . esc_url($url) . '" class="' . esc_attr($class) . '"' . $aria_current . '>' . esc_html($tab['title']) . '</a>';
        }
        echo '</nav>';
    }

    /**
     * EnqueueAssets. Minimal: enqueue the options-page JS (sticky header +
     * notice relocation) and the notice-hiding inline style, scoped to this
     * page's hook suffix. The base admin CSS is enqueued globally by
     * TemplateLoader::enqueueAssets.
     *
     * @return void
     */
    public function enqueueAssets(string $hook_suffix): void
    {
        $is_exact_settings_hook = $hook_suffix === 'settings_page_' . $this->menu_slug;
        $is_exact_parent_hook = $hook_suffix === $this->parent_slug . '_page_' . $this->menu_slug;
        $is_slug_match_hook = strpos($hook_suffix, $this->menu_slug) !== false;

        if (!$is_exact_settings_hook && !$is_exact_parent_hook && !$is_slug_match_hook) {
            return;
        }

        TemplateLoader::enqueueAssets();

        $plugin_url = '';
        if (defined('HYPERFIELDS_PLUGIN_URL') && is_string(HYPERFIELDS_PLUGIN_URL) && HYPERFIELDS_PLUGIN_URL !== '') {
            $plugin_url = HYPERFIELDS_PLUGIN_URL;
        } elseif (function_exists('plugins_url')) {
            $resolved = plugins_url('', dirname(__DIR__) . '/bootstrap.php');
            if (is_string($resolved) && $resolved !== '') {
                $plugin_url = trailingslashit($resolved);
            }
        }

        if ($plugin_url === '') {
            return;
        }

        $admin_options_script_version = defined('HYPERPRESS_VERSION') ? HYPERPRESS_VERSION : '2.0.7';
        $admin_options_script_path = dirname(__DIR__) . '/assets/js/hyperfields-admin.js';
        if (is_file($admin_options_script_path)) {
            $mtime = filemtime($admin_options_script_path);
            if ($mtime !== false) {
                $admin_options_script_version = (string) $mtime;
            }
        }

        wp_enqueue_script(
            'hyperpress-admin-options',
            $plugin_url . 'assets/js/hyperfields-admin.js',
            ['jquery'],
            $admin_options_script_version,
            true
        );

        // Hide notices before JS relocates them under the sticky header.
        // Keep selector list in sync with OptionsPage::enqueueAssets.
        $notice_selectors = implode(', ', [
            '#wpbody-content > .notice',
            '#wpbody-content > .update-nag',
            '#wpbody-content > .updated',
            '#wpbody-content > .error',
            '.wrap > .notice',
            '.wrap > .update-nag',
            '.wrap > .updated',
            '.wrap > .error',
            '.wrap.hyperpress-options-wrap > .notice',
            '.wrap.hyperpress-options-wrap > .update-nag',
            '.wrap.hyperpress-options-wrap > .updated',
            '.wrap.hyperpress-options-wrap > .error',
            '.wrap > .notice:first-child',
            '.wrap > .update-nag:first-child',
            '.wrap > .updated:first-child',
            '.wrap > .error:first-child',
        ]);

        wp_add_inline_style('hyperpress-admin', sprintf(
            '%s { opacity: 0 !important; }',
            $notice_selectors
        ));
    }
}
