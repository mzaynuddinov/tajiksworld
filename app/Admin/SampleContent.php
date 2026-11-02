<?php
/**
 * Original sample newsroom content. Safe to delete from the Posts screen.
 *
 * @package TajiksWorld
 */

declare(strict_types=1);

namespace TajiksWorld\Admin;

final class SampleContent {
	public static function import(): void {
		$categories = [
			'politics'   => ['Politics', '#1f3a5f'],
			'economy'    => ['Economy', '#8a6a2f'],
			'technology' => ['Technology', '#245c4a'],
			'world'      => ['World', '#3d3a36'],
			'tajikistan' => ['Tajikistan', '#a11d2e'],
			'sports'     => ['Sports', '#1d4e89'],
			'culture'    => ['Culture', '#6b3f69'],
		];
		$ids = [];
		foreach ($categories as $slug => [$name, $color]) {
			$existing = get_term_by('slug', $slug, 'category');
			if ($existing instanceof \WP_Term) {
				$ids[$slug] = (int) $existing->term_id;
			} else {
				$created = wp_insert_term($name, 'category', ['slug' => $slug, 'description' => sprintf('%s coverage from the TajiksWorld sample desk.', $name)]);
				$ids[$slug] = is_array($created) ? (int) $created['term_id'] : 0;
			}
			if ($ids[$slug] > 0) {
				update_term_meta($ids[$slug], '_tw_color', $color);
			}
		}
		$stories = self::stories();
		foreach ($stories as $index => $story) {
			if (get_page_by_path($story['slug'], OBJECT, 'post')) {
				continue;
			}
			$post_id = wp_insert_post([
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => $story['title'],
				'post_name'    => $story['slug'],
				'post_excerpt' => $story['excerpt'],
				'post_content' => $story['content'],
			], true);
			if (is_wp_error($post_id) || !is_int($post_id)) {
				continue;
			}
			if (!empty($ids[$story['category']])) {
				wp_set_post_categories($post_id, [$ids[$story['category']]]);
			}
			foreach ($story['flags'] as $flag) {
				update_post_meta($post_id, $flag, '1');
			}
			update_post_meta($post_id, '_tw_source', 'TajiksWorld sample desk');
			update_post_meta($post_id, '_tw_views', (string) (120 + ($index * 37)));
		}
		self::extra('tw_video', 'video-desk-river-crossing', 'Video: the new river crossing', 'A short briefing on how the crossing changes the morning commute.');
		self::extra('tw_gallery', 'photo-desk-bazaar', 'Photo desk: Saturday bazaar', 'Twelve frames from the Saturday market, shot for the sample photo desk.');
		self::extra('tw_podcast', 'podcast-briefing-one', 'Briefing, episode 1', 'The first sample episode: a 12-minute tour of the week.');
		self::extra('tw_interview', 'interview-city-architect', 'Interview: the city architect', 'A conversation about shade, courtyards, and public stairs.');
		self::extra('tw_report', 'report-winter-roads', 'Special report: winter roads', 'A sample report on how mountain roads are kept open after snowfall.');
	}

	private static function extra(string $type, string $slug, string $title, string $excerpt): void {
		if (get_page_by_path($slug, OBJECT, $type)) {
			return;
		}
		wp_insert_post([
			'post_type'    => $type,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_excerpt' => $excerpt,
			'post_content' => '<p>' . esc_html($excerpt) . '</p><p>' . esc_html__('This is original sample copy. Replace it with a real story when the newsroom is ready.', 'tajiksworld') . '</p>',
		]);
	}

	/** @return array<int, array<string, mixed>> */
	private static function stories(): array {
		$note = '<p>This is original sample copy for the TajiksWorld theme. Replace it with reported work.</p>';
		$body = static function (string $text) use ($note): string {
			$paras = '';
			foreach (preg_split("/\n+/", trim($text)) ?: [] as $paragraph) {
				$paras .= '<p>' . esc_html(trim($paragraph)) . '</p>';
			}
			return $paras . $note;
		};
		return [
			[
				'slug' => 'winter-road-crews-extend-hours',
				'title' => 'Winter road crews extend hours on the northern pass',
				'excerpt' => 'Drivers will see lights on the pass later into the evening while crews clear a week of snow.',
				'category' => 'tajikistan',
				'flags' => ['_tw_breaking', '_tw_featured'],
				'content' => $body("Crews on the northern pass will stay out two hours longer each evening this week.\nThe transport desk said the change follows three closures before dawn. Fuel and spare blades were moved to the upper station on Monday.\nResidents in the valley told the sample desk the later shift matters most for clinic vans, which had been turning back at the checkpoint."),
			],
			[
				'slug' => 'regional-grid-shares-evening-power',
				'title' => 'Regional grid agrees to share evening power',
				'excerpt' => 'A one-month arrangement moves surplus electricity toward cities that peak after dark.',
				'category' => 'economy',
				'flags' => ['_tw_featured', '_tw_trending'],
				'content' => $body("Grid operators signed a one-month arrangement to move surplus evening power across the regional interconnect.\nThe sample desk reviewed the public summary: no new plant is promised, and factories keep their existing night tariff.\nHouseholds are unlikely to see a price change before the next quarterly review."),
			],
			[
				'slug' => 'university-opens-public-data-lab',
				'title' => 'University opens a public data lab for local reporters',
				'excerpt' => 'Students and reporters can book the lab to clean spreadsheets and publish charts.',
				'category' => 'technology',
				'flags' => ['_tw_featured'],
				'content' => $body("The city university opened a small lab where reporters can book a desk, a spreadsheet, and a librarian.\nThe lab does not sell data. It teaches people how to read the files ministries already publish.\nAppointments start next Monday and are free for registered newsrooms."),
			],
			[
				'slug' => 'envoys-schedule-water-talks',
				'title' => 'Envoys schedule a new round of water talks',
				'excerpt' => 'Delegations will meet for two days to compare reservoir levels before the thaw.',
				'category' => 'world',
				'flags' => ['_tw_trending'],
				'content' => $body("Envoys will meet for two days to compare reservoir levels before the spring thaw.\nNo treaty is on the table. The agenda is a shared chart of inflows and a list of gauges that failed last winter.\nA technical note will be published after the session if every delegation agrees."),
			],
			[
				'slug' => 'education-bill-leaves-committee',
				'title' => 'Education bill leaves committee with a library clause intact',
				'excerpt' => 'The clause keeps a minimum book budget for district schools.',
				'category' => 'politics',
				'flags' => ['_tw_editors_choice'],
				'content' => $body("The education bill left committee with the library clause intact.\nThat clause sets a floor for the book budget in district schools. It does not raise teacher pay.\nThe sample desk will track the floor debate, which is scheduled after the holiday recess."),
			],
			[
				'slug' => 'squad-names-provisional-tournament-list',
				'title' => 'National squad names a provisional tournament list',
				'excerpt' => 'Twenty-three players are in camp. Three places stay open until the fitness tests.',
				'category' => 'sports',
				'flags' => ['_tw_trending'],
				'content' => $body("The national squad named twenty-three players for the first camp of the tournament window.\nThree places stay open until Friday’s fitness tests. The coaching staff said no veteran has been ruled out by injury.\nA public training session is planned for Saturday morning if the pitch dries."),
			],
			[
				'slug' => 'old-city-workshops-reopen',
				'title' => 'Old-city workshops reopen after the roof repairs',
				'excerpt' => 'Weavers and instrument makers are back in the courtyard rooms.',
				'category' => 'culture',
				'flags' => ['_tw_editors_choice', '_tw_featured'],
				'content' => $body("Weavers and instrument makers reopened their courtyard rooms after a winter of roof repairs.\nThe sample culture desk walked the lane on the first morning. Two shops were already taking orders for spring weddings.\nEntrance to the courtyard stays free. A small plaque explains which beams were replaced."),
			],
			[
				'slug' => 'payment-rules-explained',
				'title' => 'Central bank explains the new payment rules in plain language',
				'excerpt' => 'A four-page note tells shops what must appear on a receipt.',
				'category' => 'economy',
				'flags' => [],
				'content' => $body("The central bank published a four-page note on what a receipt must show under the new payment rules.\nShops have six weeks to update their terminals. Paper receipts remain acceptable for market stalls.\nThe sample desk copied nothing from the legal text except the deadline and the list of required fields."),
			],
		];
	}
}
