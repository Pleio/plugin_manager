<?php
/**
 * Displays a plugin on the admin screen.
 *
 * This file renders a plugin for the admin screen, including active/deactive,
 * manifest details & display plugin settings.
 *
 * @uses $vars['entity']
 * @uses $vars['display_reordering'] Do we display the priority reordering links?
 *
 * @package Elgg.Core
 * @subpackage Plugins
 */

$plugin = $vars['entity'];
$reordering = elgg_extract('display_reordering', $vars, false);
$priority = $plugin->getPriority();
$active = $plugin->isActive();

$can_activate = $plugin->canActivate();
$max_priority = _elgg_get_max_plugin_priority();
$actions_base = '/action/admin/plugins/';
$css_id = preg_replace('/[^a-z0-9-]/i', '-', $plugin->getID());

// build reordering links
$links = '';
$classes = array('elgg-plugin');

if ($reordering) {
	$classes[] = 'elgg-state-draggable';
	
	// top and up link only if not at top
	if ($priority > 1) {
		$top_url = elgg_http_add_url_query_elements($actions_base . 'set_priority', array(
			'plugin_guid' => $plugin->guid,
			'priority' => 'first',
			'is_action' => true
		));

		$links .= "<li>" . elgg_view('output/url', array(
			'href' => $top_url,
			'text' => elgg_echo('top'),
			'is_action' => true,
			'is_trusted' => true,
		)) . "</li>";

		$up_url = elgg_http_add_url_query_elements($actions_base . 'set_priority', array(
			'plugin_guid' => $plugin->guid,
			'priority' => '-1',
			'is_action' => true
		));

		$links .= "<li>" . elgg_view('output/url', array(
			'href' => $up_url,
			'text' => elgg_echo('up'),
			'is_action' => true,
			'is_trusted' => true,
		)) . "</li>";
	}

	// down and bottom links only if not at bottom
	if ($priority < $max_priority) {
		$down_url = elgg_http_add_url_query_elements($actions_base . 'set_priority', array(
			'plugin_guid' => $plugin->guid,
			'priority' => '+1',
			'is_action' => true
		));

		$links .= "<li>" . elgg_view('output/url', array(
			'href' => $down_url,
			'text' => elgg_echo('down'),
			'is_action'	=> true,
			'is_trusted' => true,
		)) . "</li>";

		$bottom_url = elgg_http_add_url_query_elements($actions_base . 'set_priority', array(
			'plugin_guid' => $plugin->guid,
			'priority' => 'last',
			'is_action' => true
		));

		$links .= "<li>" . elgg_view('output/url', array(
			'href' 		=> $bottom_url,
			'text'		=> elgg_echo('bottom'),
			'is_action'	=> true,
			'is_trusted' => true,
		)) . "</li>";
	}
	
	if ($links) {
		$links = '<ul class="elgg-menu plugin-manager-list-reordering">' . $links . '</ul>';
	}
} else {
	$classes[] = 'elgg-state-undraggable';
}


// activate / deactivate links

// always let them deactivate
$options = array(
	'is_action' => true,
	'is_trusted' => true,
);
if ($active) {
	$classes[] = 'elgg-state-active';
	$action = 'deactivate';
	$options['text'] = "<span title='" . elgg_echo('admin:plugins:deactivate') . "' class='plugin-manager-icon plugin-manager-icon-deactivate'></span>";
	
	if (!$can_activate) {
		$classes[] = 'elgg-state-active elgg-state-cannot-activate';
	}
} else if ($can_activate) {
	$classes[] = 'elgg-state-inactive';
	$action = 'activate';
	$options['text'] = "<span title='" . elgg_echo('admin:plugins:activate') . "' class='plugin-manager-icon plugin-manager-icon-activate'></span>";
	
} else {
	$classes[] = 'elgg-state-inactive elgg-state-cannot-activate';
	$action = '';
	$options['text'] = elgg_echo('admin:plugins:cannot_activate');
	$options['text'] = "<span title='" . elgg_echo('admin:plugins:cannot_activate') . "' class='plugin-manager-icon plugin-manager-icon-cannot-activate'></span>";
	
	$options['disabled'] = 'disabled';
}

if ($action) {
	$url = elgg_http_add_url_query_elements($actions_base . $action, array(
		'plugin_guids[]' => $plugin->guid
	));

	$options['href'] = $url;
}
$action_button = elgg_view('output/url', $options);

$action_button = elgg_trigger_plugin_hook("action_button", "plugin", array("entity" => $plugin), $action_button);

// Display categories and make category classes
$categories = $plugin->getManifest()->getCategories();

$categories[] = "all";

if (!in_array("bundled", $categories)) {
	$categories[] = "nonbundled";
}

if ($active) {
	$categories[] = "active";
} else {
	$categories[] = "inactive";
}

$categories_html = '';
if ($categories) {
	foreach ($categories as $category) {
		$css_class = preg_replace('/[^a-z0-9-]/i', '-', $category);
		$classes[] = "elgg-plugin-category-$css_class";
	}
}

// metadata
$description = elgg_view('output/longtext', array('value' => $plugin->getManifest()->getDescription()));

$settings_view_old = 'settings/' . $plugin->getID() . '/edit';
$settings_view_new = 'plugins/' . $plugin->getID() . '/settings';
$settings_link = '';
if (elgg_view_exists($settings_view_old) || elgg_view_exists($settings_view_new)) {
	$link = elgg_get_site_url() . "admin/plugin_settings/" . $plugin->getID();
	$settings_link = "<a class='elgg-plugin-settings' href='$link' title='" . elgg_echo('settings') . "'>" . elgg_view_icon("settings-alt") . "</a>";
}

?>
<div class="<?php echo implode(' ', $classes); ?>" id="<?php echo $css_id; ?>">
	<div class="elgg-image-block">
		<div class="elgg-image">
			<div>
				<?php echo $action_button; ?>
			</div>
		</div>
		<div class="elgg-body">
			<div class="elgg-head">
				<?php
					echo $links;
					$url_options = array(
						"href" => "ajax/view/plugin_manager/plugin_details?guid=" . $plugin->getGUID(),
						"text" => $plugin->getManifest()->getName(),
						"class" => "elgg-lightbox",
						"data-colorbox-opts" => '{"className": "plugin-manager-lightbox", "closeButton": false}'
					);
					echo elgg_view("output/url", $url_options);
					
					echo " ". $settings_link;
				?>
				<span class="plugin-manager-list-description">
					<?php echo $description;?>
				</span>
			</div>
		</div>
	</div>
</div>
