<?php
/**
 * Builder element registry. Pure data — safe to load without WordPress.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

$align = ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'];
$post_types = [
	'post'         => 'News',
	'tw_video'     => 'Videos',
	'tw_gallery'   => 'Galleries',
	'tw_podcast'   => 'Podcasts',
	'tw_interview' => 'Interviews',
	'tw_report'    => 'Special Reports',
];

return [
	'heading' => [
		'label' => 'Heading', 'category' => 'basic', 'nesting' => 'none',
		'fields' => [
			['key' => 'text', 'type' => 'text', 'label' => 'Text', 'default' => 'Heading'],
			['key' => 'tag', 'type' => 'select', 'label' => 'Level', 'default' => 'h2', 'options' => ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6']],
			['key' => 'align', 'type' => 'select', 'label' => 'Alignment', 'default' => 'left', 'options' => $align],
		],
	],
	'text' => [
		'label' => 'Text Editor', 'category' => 'basic', 'nesting' => 'none',
		'fields' => [
			['key' => 'content', 'type' => 'textarea', 'label' => 'Content', 'default' => ''],
			['key' => 'align', 'type' => 'select', 'label' => 'Alignment', 'default' => 'left', 'options' => $align],
		],
	],
	'image' => [
		'label' => 'Image', 'category' => 'media', 'nesting' => 'none',
		'fields' => [
			['key' => 'image_id', 'type' => 'image', 'label' => 'Image', 'default' => 0],
			['key' => 'alt', 'type' => 'text', 'label' => 'Alt text', 'default' => ''],
			['key' => 'caption', 'type' => 'text', 'label' => 'Caption', 'default' => ''],
			['key' => 'url', 'type' => 'url', 'label' => 'Link', 'default' => ''],
			['key' => 'align', 'type' => 'select', 'label' => 'Alignment', 'default' => 'center', 'options' => $align],
		],
	],
	'gallery' => [
		'label' => 'Image Gallery', 'category' => 'media', 'nesting' => 'none',
		'fields' => [
			['key' => 'images', 'type' => 'images', 'label' => 'Images', 'default' => ''],
			['key' => 'columns', 'type' => 'select', 'label' => 'Columns', 'default' => '3', 'options' => ['2' => '2', '3' => '3', '4' => '4']],
		],
	],
	'video' => [
		'label' => 'Video', 'category' => 'media', 'nesting' => 'none',
		'fields' => [
			['key' => 'url', 'type' => 'url', 'label' => 'Video URL', 'default' => ''],
			['key' => 'caption', 'type' => 'text', 'label' => 'Caption', 'default' => ''],
		],
	],
	'button' => [
		'label' => 'Button', 'category' => 'basic', 'nesting' => 'none',
		'fields' => [
			['key' => 'label', 'type' => 'text', 'label' => 'Label', 'default' => 'Read more'],
			['key' => 'url', 'type' => 'url', 'label' => 'URL', 'default' => '#'],
			['key' => 'style', 'type' => 'select', 'label' => 'Style', 'default' => 'primary', 'options' => ['primary' => 'Primary', 'secondary' => 'Secondary', 'ghost' => 'Ghost']],
			['key' => 'align', 'type' => 'select', 'label' => 'Alignment', 'default' => 'left', 'options' => $align],
			['key' => 'new_tab', 'type' => 'checkbox', 'label' => 'Open in a new tab', 'default' => false],
		],
	],
	'icon' => [
		'label' => 'Icon', 'category' => 'basic', 'nesting' => 'none',
		'fields' => [
			['key' => 'name', 'type' => 'select', 'label' => 'Icon', 'default' => 'arrow', 'options' => ['arrow' => 'Arrow', 'search' => 'Search', 'play' => 'Play', 'mail' => 'Mail', 'clock' => 'Clock', 'user' => 'User', 'rss' => 'RSS']],
			['key' => 'url', 'type' => 'url', 'label' => 'Link', 'default' => ''],
			['key' => 'size', 'type' => 'number', 'label' => 'Size', 'default' => 28, 'min' => 12, 'max' => 96],
		],
	],
	'divider' => [
		'label' => 'Divider', 'category' => 'basic', 'nesting' => 'none',
		'fields' => [
			['key' => 'style', 'type' => 'select', 'label' => 'Style', 'default' => 'solid', 'options' => ['solid' => 'Solid', 'dashed' => 'Dashed', 'double' => 'Double']],
		],
	],
	'spacer' => [
		'label' => 'Spacer', 'category' => 'basic', 'nesting' => 'none',
		'fields' => [
			['key' => 'height', 'type' => 'number', 'label' => 'Height (px)', 'default' => 32, 'min' => 4, 'max' => 240],
		],
	],
	'container' => [
		'label' => 'Container', 'category' => 'layout', 'nesting' => 'blocks',
		'fields' => [
			['key' => 'max_width', 'type' => 'number', 'label' => 'Max width (px)', 'default' => 1100, 'min' => 320, 'max' => 1600],
			['key' => 'padding', 'type' => 'number', 'label' => 'Padding (px)', 'default' => 0, 'min' => 0, 'max' => 80],
		],
	],
	'columns' => [
		'label' => 'Columns', 'category' => 'layout', 'nesting' => 'columns',
		'fields' => [
			['key' => 'count', 'type' => 'select', 'label' => 'Columns', 'default' => '2', 'options' => ['2' => '2', '3' => '3', '4' => '4']],
			['key' => 'gap', 'type' => 'number', 'label' => 'Gap (px)', 'default' => 24, 'min' => 0, 'max' => 80],
		],
	],
	'grid' => [
		'label' => 'Grid', 'category' => 'layout', 'nesting' => 'blocks',
		'fields' => [
			['key' => 'columns', 'type' => 'select', 'label' => 'Columns', 'default' => '3', 'options' => ['2' => '2', '3' => '3', '4' => '4']],
			['key' => 'gap', 'type' => 'number', 'label' => 'Gap (px)', 'default' => 16, 'min' => 0, 'max' => 64],
		],
	],
	'latest_news' => [
		'label' => 'Latest News', 'category' => 'news', 'nesting' => 'none',
		'fields' => [
			['key' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Latest'],
			['key' => 'count', 'type' => 'number', 'label' => 'Count', 'default' => 6, 'min' => 1, 'max' => 24],
			['key' => 'columns', 'type' => 'select', 'label' => 'Columns', 'default' => '3', 'options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4']],
			['key' => 'category', 'type' => 'category', 'label' => 'Category', 'default' => ''],
			['key' => 'post_type', 'type' => 'select', 'label' => 'Content type', 'default' => 'post', 'options' => $post_types],
			['key' => 'show_excerpt', 'type' => 'checkbox', 'label' => 'Show excerpt', 'default' => true],
			['key' => 'show_image', 'type' => 'checkbox', 'label' => 'Show image', 'default' => true],
		],
	],
	'trending_news' => [
		'label' => 'Trending News', 'category' => 'news', 'nesting' => 'none',
		'fields' => [
			['key' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Trending'],
			['key' => 'count', 'type' => 'number', 'label' => 'Count', 'default' => 5, 'min' => 1, 'max' => 12],
		],
	],
	'featured_news' => [
		'label' => 'Featured News', 'category' => 'news', 'nesting' => 'none',
		'fields' => [
			['key' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => ''],
			['key' => 'count', 'type' => 'number', 'label' => 'Count', 'default' => 4, 'min' => 1, 'max' => 8],
			['key' => 'category', 'type' => 'category', 'label' => 'Category', 'default' => ''],
		],
	],
	'breaking_news' => [
		'label' => 'Breaking News', 'category' => 'news', 'nesting' => 'none',
		'fields' => [
			['key' => 'label', 'type' => 'text', 'label' => 'Label', 'default' => 'Breaking'],
			['key' => 'count', 'type' => 'number', 'label' => 'Count', 'default' => 8, 'min' => 1, 'max' => 20],
		],
	],
	'popular_news' => [
		'label' => 'Popular News', 'category' => 'news', 'nesting' => 'none',
		'fields' => [
			['key' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Popular'],
			['key' => 'count', 'type' => 'number', 'label' => 'Count', 'default' => 5, 'min' => 1, 'max' => 12],
		],
	],
	'news_categories' => [
		'label' => 'News Categories', 'category' => 'news', 'nesting' => 'none',
		'fields' => [
			['key' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'Sections'],
			['key' => 'show_count', 'type' => 'checkbox', 'label' => 'Show counts', 'default' => true],
			['key' => 'parents_only', 'type' => 'checkbox', 'label' => 'Top-level only', 'default' => false],
		],
	],
	'news_carousel' => [
		'label' => 'News Carousel', 'category' => 'news', 'nesting' => 'none',
		'fields' => [
			['key' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => ''],
			['key' => 'count', 'type' => 'number', 'label' => 'Count', 'default' => 5, 'min' => 1, 'max' => 12],
			['key' => 'category', 'type' => 'category', 'label' => 'Category', 'default' => ''],
			['key' => 'post_type', 'type' => 'select', 'label' => 'Content type', 'default' => 'post', 'options' => $post_types],
		],
	],
	'news_grid' => [
		'label' => 'News Grid', 'category' => 'news', 'nesting' => 'none',
		'fields' => [
			['key' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => ''],
			['key' => 'count', 'type' => 'number', 'label' => 'Count', 'default' => 4, 'min' => 1, 'max' => 12],
			['key' => 'columns', 'type' => 'select', 'label' => 'Columns', 'default' => '4', 'options' => ['2' => '2', '3' => '3', '4' => '4']],
			['key' => 'category', 'type' => 'category', 'label' => 'Category', 'default' => ''],
			['key' => 'post_type', 'type' => 'select', 'label' => 'Content type', 'default' => 'post', 'options' => $post_types],
			['key' => 'show_excerpt', 'type' => 'checkbox', 'label' => 'Show excerpt', 'default' => false],
		],
	],
	'news_list' => [
		'label' => 'News List', 'category' => 'news', 'nesting' => 'none',
		'fields' => [
			['key' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => ''],
			['key' => 'count', 'type' => 'number', 'label' => 'Count', 'default' => 5, 'min' => 1, 'max' => 20],
			['key' => 'category', 'type' => 'category', 'label' => 'Category', 'default' => ''],
			['key' => 'post_type', 'type' => 'select', 'label' => 'Content type', 'default' => 'post', 'options' => $post_types],
			['key' => 'show_thumb', 'type' => 'checkbox', 'label' => 'Show thumbnail', 'default' => true],
		],
	],
	'author_box' => [
		'label' => 'Author Box', 'category' => 'news', 'nesting' => 'none',
		'fields' => [
			['key' => 'user_id', 'type' => 'number', 'label' => 'User ID (0 = current article author)', 'default' => 0, 'min' => 0, 'max' => 999999],
			['key' => 'show_bio', 'type' => 'checkbox', 'label' => 'Show biography', 'default' => true],
		],
	],
	'advertisement' => [
		'label' => 'Advertisement', 'category' => 'engagement', 'nesting' => 'none',
		'fields' => [
			['key' => 'placement', 'type' => 'select', 'label' => 'Placement', 'default' => 'homepage', 'options' => [
				'header' => 'Header', 'homepage' => 'Homepage', 'sidebar' => 'Sidebar', 'in_article' => 'In article',
				'between' => 'Between news', 'footer' => 'Footer', 'mobile' => 'Mobile',
			]],
		],
	],
	'newsletter' => [
		'label' => 'Newsletter Subscription', 'category' => 'engagement', 'nesting' => 'none',
		'fields' => [
			['key' => 'title', 'type' => 'text', 'label' => 'Title', 'default' => 'The morning briefing'],
			['key' => 'text', 'type' => 'textarea', 'label' => 'Text', 'default' => 'One concise briefing in your inbox. No noise.'],
			['key' => 'button', 'type' => 'text', 'label' => 'Button label', 'default' => 'Subscribe'],
		],
	],
	'social_links' => [
		'label' => 'Social Media Links', 'category' => 'engagement', 'nesting' => 'none',
		'fields' => [
			['key' => 'style', 'type' => 'select', 'label' => 'Style', 'default' => 'text', 'options' => ['text' => 'Text', 'icons' => 'Icons']],
		],
	],
	'search_form' => [
		'label' => 'Search Form', 'category' => 'engagement', 'nesting' => 'none',
		'fields' => [
			['key' => 'placeholder', 'type' => 'text', 'label' => 'Placeholder', 'default' => 'Search the newsroom'],
			['key' => 'style', 'type' => 'select', 'label' => 'Style', 'default' => 'inline', 'options' => ['inline' => 'Inline', 'icon' => 'Icon button']],
		],
	],
	'breadcrumbs' => [
		'label' => 'Breadcrumbs', 'category' => 'engagement', 'nesting' => 'none',
		'fields' => [],
	],
	'statistics' => [
		'label' => 'Statistics', 'category' => 'engagement', 'nesting' => 'none',
		'fields' => [
			['key' => 'mode', 'type' => 'select', 'label' => 'Source', 'default' => 'auto', 'options' => ['auto' => 'Live site counts', 'manual' => 'Manual numbers']],
			['key' => 'label_1', 'type' => 'text', 'label' => 'Label 1', 'default' => 'Stories'],
			['key' => 'value_1', 'type' => 'text', 'label' => 'Value 1', 'default' => '128'],
			['key' => 'label_2', 'type' => 'text', 'label' => 'Label 2', 'default' => 'Sections'],
			['key' => 'value_2', 'type' => 'text', 'label' => 'Value 2', 'default' => '12'],
			['key' => 'label_3', 'type' => 'text', 'label' => 'Label 3', 'default' => 'Languages'],
			['key' => 'value_3', 'type' => 'text', 'label' => 'Value 3', 'default' => '3'],
			['key' => 'label_4', 'type' => 'text', 'label' => 'Label 4', 'default' => 'Bureaus'],
			['key' => 'value_4', 'type' => 'text', 'label' => 'Value 4', 'default' => '6'],
		],
	],
	'custom_html' => [
		'label' => 'Custom HTML', 'category' => 'basic', 'nesting' => 'none',
		'fields' => [
			['key' => 'html', 'type' => 'html', 'label' => 'HTML', 'default' => ''],
		],
	],
	'logo' => [
		'label' => 'Logo', 'category' => 'site', 'nesting' => 'none',
		'fields' => [
			['key' => 'show_tagline', 'type' => 'checkbox', 'label' => 'Show tagline', 'default' => false],
		],
	],
	'navigation' => [
		'label' => 'Navigation', 'category' => 'site', 'nesting' => 'none',
		'fields' => [
			['key' => 'location', 'type' => 'select', 'label' => 'Menu', 'default' => 'primary', 'options' => ['primary' => 'Primary', 'footer' => 'Footer', 'topbar' => 'Top bar']],
		],
	],
	'language_switcher' => [
		'label' => 'Language Switcher', 'category' => 'site', 'nesting' => 'none',
		'fields' => [],
	],
	'dark_toggle' => [
		'label' => 'Dark Mode Toggle', 'category' => 'site', 'nesting' => 'none',
		'fields' => [],
	],
	'copyright' => [
		'label' => 'Copyright', 'category' => 'site', 'nesting' => 'none',
		'fields' => [
			['key' => 'text', 'type' => 'text', 'label' => 'Text ({year} and {site} are replaced)', 'default' => '© {year} {site}. All rights reserved.'],
		],
	],
];
