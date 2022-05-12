#!/usr/bin/env php
<?php

/**
 * # artflow.ai Portrait Scraper
 *
 * This script scrapes CC-BY portrait images from artflow.ai. Images in the
 * galleries are featured randomly, so duplicates will become increasingly
 * common as scraping continues. Filenames are generated with the slug and
 * numeric ID, and are not re-downloaded if the file already exists.
 *
 * ## Usage
 *
 * This script works with the default PHP modules, plus ImageMagick. It requires
 * no Composer modules.
 *
 * To scrape the Editor's Choice and Community Creations galleries directly, the
 * script can be invoked from the command line or from a cron job using
 * `./scrape.php` or `php scrape.php`.
 *
 * If you wish to scrape content directly from your browser (eg. the gallery of
 * your own creations), you may do so by opening the web inspector and copying
 * the relevant HTML snippets. You can then pipe the content directly into the
 * scrape command, like with the following:
 *
 *     # paste directly from clipboard (Mac)
 *     pbpaste | php scrape.php
 *
 *     # paste directly from clipboard (Linux)
 *     xsel --clipboard --output | php scrape.php
 *
 *     # paste manually into terminal
 *     php scrape.php <<EOF
 *     <paste some HTML here>
 *     EOF
 *
 * ## Self-hosting notes
 *
 * All scraped content is available at
 * https://github.com/initiative-sh/artflow-portraits, so you should not need to
 * run this yourself.
 *
 * As always, if you do intend to run this script yourself, please be respectful
 * of target server resources. artflow.ai has graciously chosen to use a free
 * license for their material and share it online, if not in the most accessible
 * format.
 *
 * This script runs on a cron making one request per minute with a unique,
 * identifiable user agent to allow the host to throttle or contact us if
 * necessary. Please assign a different user agent when running it yourself
 * according to the same principles.
 */

const USER_AGENT = 'github.com/initiative-sh/artflow-portraits';

/**
 * Copyright © 2021 Mikkel Paulson
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the “Software”), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

if (stream_isatty(STDIN)) {
    return scrape_listing();
} else {
    $input_raw = file_get_contents('php://stdin');

    if (preg_match_all('/([a-f0-9]{32})\.webp/', $input_raw, $match, PREG_PATTERN_ORDER)) {
        $filenames = $match[1];
    } else {
        return scrape_listing();
    }

    if (preg_match_all("/id=\"my_work_title\".*?>([^<]*)/", $input_raw, $match, PREG_PATTERN_ORDER)) {
        $text_prompts = array_values(array_filter(
            $match[1],
            fn($v) => $v !== "Red paint cup",
        ));
    } else {
        echo "Missing expected value: text_prompt\n";
        exit(1);
    }

    if (preg_match_all("/twitter_div_(\d+)/", $input_raw, $match, PREG_PATTERN_ORDER)) {
        $ids = $match[1];
    } else {
        echo "Missing expected value: id\n";
        exit(1);
    }

    if (count($filenames) !== count($text_prompts) || count($filenames) !== count($ids)) {
        echo "The number of matches for each set much be the same.\n";
        echo "filenames: " . var_export($filenames, true);
        echo "text_prompts: " . var_export($text_prompts, true);
        echo "ids: " . var_export($ids, true);
        exit(1);
    }

    for ($i = 0; $i < count($filenames); $i++) {
        download_result($text_prompts[$i], $ids[$i], $filenames[$i]);
    }
}

function scrape_single(string $text_prompt, string $id) {
    $results_raw = file_get_contents(
        "https://artflow.ai/check_status",
        false,
        stream_context_create([
            "http" => [
                "method" => "POST",
                "content" => http_build_query(["my_work_id" => $id]),
                "header" => [
                    "User-agent: " . USER_AGENT,
                ],
            ],
        ]),
    );

    echo trim($results_raw) . "\n";

    $results = json_decode($results_raw);

    if (isset($results->filename)) {
        return download_result($text_prompt, $id, $results->filename);
    } else {
        echo "Unexpected result: " . trim(var_export($results, true));
        exit(1);
    }
}

function scrape_listing() {
    $source_url = mt_rand(0, 9)
        ? "https://artflow.ai/api/show_community_work"
        : "https://artflow.ai/api/show_editor_choice";

    echo "Scraping {$source_url}\n";

    $results_raw = file_get_contents(
        $source_url,
        false,
        stream_context_create([
            "http" => [
                "method" => "POST",
                "header" => [
                    "User-agent: " . USER_AGENT,
                ],
            ]
        ]),
    );

    echo trim($results_raw) . "\n";

    $results = json_decode($results_raw);

    foreach ($results as $result) {
        download_result(
            $result->text_prompt,
            $result->id,
            $result->filename,
        );
    }
}

function download_result(string $text_prompt, string $id, string $filename) {
    $source_url = "https://artflowbucket-new.s3.amazonaws.com/generated/{$filename}.webp";
    $slug = trim(preg_replace("/\W+/", "-", $text_prompt), '-');

    $output_filename = sprintf(
        '%s/images/%02d/%s%d.jpg',
        __DIR__,
        $id % 100,
        $slug ? "$slug-" : '',
        $id,
    );

    if (file_exists($output_filename)) {
        echo "$output_filename already exists.\n";
    } else {
        echo "$output_filename <- $source_url\n";

        $image = new Imagick();
        $image->readImageFile(fopen($source_url, "rb"));
        $image->setImageFormat("jpeg");
        $image->writeImage($output_filename);
    }
}
