<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Common;
use App\Models\General_Setting;
use App\Models\Language;
use App\Models\Season;
use App\Models\TVShow;
use App\Models\TVShow_Video;
use App\Models\Type;
use App\Models\Video;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class YouTubeImporterController extends Controller
{
    public $common;
    private $folder = 'content';

    public function __construct()
    {
        $this->common = new Common();
    }

    public function index()
    {
        try {
            $apiKey = (string) (General_Setting::where('key', 'youtube_api_key')->value('value') ?? '');
            $shows = TVShow::orderBy('id', 'desc')->get();
            $seasons = Season::where('status', 1)->orderBy('sort_order', 'asc')->get();
            $types = Type::where('status', 1)->orderBy('sort_order', 'asc')->get();
            $categories = Category::where('status', 1)->orderBy('sort_order', 'asc')->get();
            $languages = Language::where('status', 1)->orderBy('sort_order', 'asc')->get();

            return view('admin.youtube_importer.index', compact(
                'apiKey',
                'shows',
                'seasons',
                'types',
                'categories',
                'languages'
            ));
        } catch (Exception $e) {
            return response()->json(['status' => 400, 'errors' => $e->getMessage()]);
        }
    }

    public function saveApiKey(Request $request)
    {
        try {
            $key = trim((string) $request->input('youtube_api_key', ''));
            $existing = General_Setting::where('key', 'youtube_api_key')->first();
            if ($existing) {
                $existing->value = $key;
                $existing->save();
            } else {
                General_Setting::create([
                    'key'   => 'youtube_api_key',
                    'value' => $key,
                ]);
            }

            return response()->json([
                'status'  => 200,
                'success' => $key !== '' ? 'YouTube Data API v3 Key saved successfully.' : 'YouTube API Key cleared (Free mode active).',
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 400, 'errors' => $e->getMessage()]);
        }
    }

    public function fetch(Request $request)
    {
        @set_time_limit(180);

        try {
            $sourceType = $request->input('source_type', 'url');
            $apiKey = trim((string) ($request->input('api_key') ?: General_Setting::where('key', 'youtube_api_key')->value('value') ?: ''));
            $limit = max(1, min(200, (int) $request->input('limit', 50)));
            $pageToken = trim((string) $request->input('page_token', ''));

            if ($sourceType === 'links') {
                $rawLinks = (string) $request->input('video_links', '');
                $videoIds = $this->extractVideoIdsFromText($rawLinks);
                if (empty($videoIds)) {
                    return response()->json(['status' => 400, 'errors' => 'No valid YouTube video URLs or IDs found in input.']);
                }
                $videoIds = array_slice($videoIds, 0, 200);
                $videos = $apiKey !== ''
                    ? $this->fetchVideosDetailsViaApi($videoIds, $apiKey)
                    : $this->fetchVideosDetailsViaOembed($videoIds);

                $videos = $this->attachExistingFlags($videos);
                return response()->json([
                    'status' => 200,
                    'mode'   => $apiKey !== '' ? 'api' : 'oembed',
                    'source' => ['title' => 'Pasted Video Links', 'type' => 'links', 'thumbnail' => $videos[0]['thumbnail'] ?? ''],
                    'videos' => $videos,
                    'next_page_token' => '',
                ]);
            }

            $url = trim((string) $request->input('url', ''));
            if ($url === '') {
                return response()->json(['status' => 400, 'errors' => 'Please enter a YouTube Channel or Playlist URL.']);
            }

            $parsed = $this->parseYouTubeSource($url);

            // If API key is configured, use official YouTube Data API v3 for full pagination & exact metadata
            if ($apiKey !== '') {
                $result = $this->fetchViaApi($parsed, $apiKey, $limit, $pageToken);
                $result['videos'] = $this->attachExistingFlags($result['videos']);
                return response()->json(array_merge(['status' => 200, 'mode' => 'api'], $result));
            }

            // Free / Zero-API-Key Mode (Channel/Playlist RSS + HTML ytInitialData extraction)
            $result = $this->fetchWithoutApiKey($parsed);
            $result['videos'] = $this->attachExistingFlags($result['videos']);

            return response()->json(array_merge(['status' => 200, 'mode' => 'free'], $result));
        } catch (Exception $e) {
            return response()->json(['status' => 400, 'errors' => $e->getMessage()]);
        }
    }

    public function import(Request $request)
    {
        @set_time_limit(300);

        try {
            $importTarget = $request->input('import_target', 'tv_show');
            $videosJson = $request->input('videos', '[]');
            $videos = is_array($videosJson) ? $videosJson : json_decode($videosJson, true);

            if (!is_array($videos) || count($videos) === 0) {
                return response()->json(['status' => 400, 'errors' => 'Please select at least one video to import.']);
            }

            $isPremium = (int) $request->input('is_premium', 0) === 1 ? 1 : 0;
            $skipDuplicates = (int) $request->input('skip_duplicates', 1) === 1;
            $sortDirection = $request->input('sort_direction', 'as_listed');

            if ($sortDirection === 'reverse') {
                $videos = array_reverse($videos);
            }

            $storageType = (int) (Storage_Type() ?: 1);

            if ($importTarget === 'tv_show') {
                $seasonId = (int) $request->input('season_id', 1);
                $showMode = $request->input('show_mode', 'existing');

                if ($showMode === 'new') {
                    $validator = Validator::make($request->all(), [
                        'new_show_name' => 'required|min:2',
                        'new_show_type_id' => 'required',
                        'new_show_category_id' => 'required|array|min:1',
                        'new_show_language_id' => 'required|array|min:1',
                    ]);
                    if ($validator->fails()) {
                        return response()->json(['status' => 400, 'errors' => $validator->errors()->all()]);
                    }

                    $typeObj = Type::find($request->input('new_show_type_id'));
                    $posterUrl = trim((string) $request->input('new_show_poster_url', ''));
                    if ($posterUrl === '' && !empty($videos[0]['id'])) {
                        $posterUrl = 'https://i.ytimg.com/vi/' . $videos[0]['id'] . '/hqdefault.jpg';
                    }

                    $savedThumb = '';
                    $file = $request->file('new_show_thumbnail');
                    if ($file) {
                        $savedThumb = $this->common->saveImage($file, $this->folder, 'show_', $storageType);
                    } elseif ($posterUrl !== '') {
                        $savedThumb = $this->downloadRemoteImage($posterUrl, 'show_', $storageType);
                    }

                    $show = new TVShow();
                    $show->type_id = (int) $request->input('new_show_type_id');
                    $show->video_type = $typeObj ? (int) $typeObj->type : 2;
                    $show->channel_id = 0;
                    $show->producer_id = 0;
                    $show->category_id = implode(',', $request->input('new_show_category_id', []));
                    $show->language_id = implode(',', $request->input('new_show_language_id', []));
                    $show->cast_id = '';
                    $show->name = trim((string) $request->input('new_show_name'));
                    $show->storage_type = $storageType;
                    $show->thumbnail = $savedThumb;
                    $show->landscape = $savedThumb;
                    $show->trailer_storage_type = $storageType;
                    $show->trailer_type = !empty($videos[0]['id']) ? 'youtube' : '';
                    $show->trailer_url = !empty($videos[0]['id']) ? ('https://www.youtube.com/watch?v=' . $videos[0]['id']) : '';
                    $show->description = trim((string) $request->input('new_show_description', '')) ?: ($show->name . ' — imported episodes on ' . App_Name());
                    $show->release_date = date('Y-m-d');
                    $show->is_title = 1;
                    $show->is_comment = 1;
                    $show->is_like = 1;
                    $show->is_rent = 0;
                    $show->price = 0;
                    $show->rent_day = 0;
                    $show->avg_rating = 0;
                    $show->total_review = 0;
                    $show->total_view = 0;
                    $show->total_like = 0;
                    $show->status = 1;
                    $show->save();

                    $showId = (int) $show->id;
                    $showTypeId = (int) $show->type_id;
                } else {
                    $showId = (int) $request->input('show_id', 0);
                    $show = TVShow::find($showId);
                    if (!$show) {
                        return response()->json(['status' => 400, 'errors' => 'Please select a valid TV Show.']);
                    }
                    $showTypeId = (int) $show->type_id;
                }

                $existingUrls = TVShow_Video::where('show_id', $showId)
                    ->where('video_upload_type', 'youtube')
                    ->pluck('video_320')
                    ->toArray();
                $existingIds = $this->extractVideoIdsFromArray($existingUrls);

                $maxOrder = (int) TVShow_Video::where('show_id', $showId)->max('sort_order');
                $imported = 0;
                $skipped = 0;

                foreach ($videos as $item) {
                    $vid = trim((string) ($item['id'] ?? ''));
                    if ($vid === '') {
                        continue;
                    }
                    if ($skipDuplicates && in_array($vid, $existingIds, true)) {
                        $skipped++;
                        continue;
                    }

                    $maxOrder++;
                    $watchUrl = 'https://www.youtube.com/watch?v=' . $vid;
                    $thumbUrl = trim((string) ($item['thumbnail'] ?? '')) ?: ('https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg');
                    $savedImg = $this->downloadRemoteImage($thumbUrl, 'show_ep_', $storageType);
                    if ($savedImg === '' && $thumbUrl !== ('https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg')) {
                        $savedImg = $this->downloadRemoteImage('https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg', 'show_ep_', $storageType);
                    }

                    $ep = new TVShow_Video();
                    $ep->show_id = $showId;
                    $ep->season_id = $seasonId;
                    $ep->name = mb_substr(trim((string) ($item['title'] ?? 'Episode ' . $maxOrder)), 0, 250);
                    $ep->storage_type = $storageType;
                    $ep->thumbnail = $savedImg;
                    $ep->landscape = $savedImg;
                    $ep->description = trim((string) ($item['description'] ?? ''));
                    $ep->video_storage_type = $storageType;
                    $ep->video_upload_type = 'youtube';
                    $ep->video_320 = $watchUrl;
                    $ep->video_480 = $watchUrl;
                    $ep->video_720 = $watchUrl;
                    $ep->video_1080 = $watchUrl;
                    $ep->video_extension = '';
                    $ep->video_duration = max(0, (int) ($item['duration_ms'] ?? 0));
                    $ep->subtitle_storage_type = $storageType;
                    $ep->subtitle_type = 'external';
                    $ep->subtitle_lang_1 = '';
                    $ep->subtitle_1 = '';
                    $ep->subtitle_lang_2 = '';
                    $ep->subtitle_2 = '';
                    $ep->subtitle_lang_3 = '';
                    $ep->subtitle_3 = '';
                    $ep->is_premium = $isPremium;
                    $ep->is_title = 1;
                    $ep->is_download = 0;
                    $ep->total_view = 0;
                    $ep->sort_order = $maxOrder;
                    $ep->status = 1;
                    $ep->save();

                    $existingIds[] = $vid;
                    $imported++;
                }

                return response()->json([
                    'status'       => 200,
                    'imported'     => $imported,
                    'skipped'      => $skipped,
                    'redirect_url' => route('admin.tvshow.episode.index', ['id' => $showId, 'type_id' => $showTypeId]),
                    'success'      => "Imported {$imported} episode(s) cleanly" . ($skipped > 0 ? " ({$skipped} duplicate(s) skipped)." : '.'),
                ]);
            }

            // Import as Movies / Individual Videos
            $validator = Validator::make($request->all(), [
                'movie_type_id'     => 'required',
                'movie_category_id' => 'required|array|min:1',
                'movie_language_id' => 'required|array|min:1',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 400, 'errors' => $validator->errors()->all()]);
            }

            $movieTypeId = (int) $request->input('movie_type_id');
            $typeObj = Type::find($movieTypeId);
            $videoType = $typeObj ? (int) $typeObj->type : 1;
            $catStr = implode(',', $request->input('movie_category_id', []));
            $langStr = implode(',', $request->input('movie_language_id', []));

            $existingUrls = Video::where('video_upload_type', 'youtube')->pluck('video_320')->toArray();
            $existingIds = $this->extractVideoIdsFromArray($existingUrls);

            $imported = 0;
            $skipped = 0;

            foreach ($videos as $item) {
                $vid = trim((string) ($item['id'] ?? ''));
                if ($vid === '') {
                    continue;
                }
                if ($skipDuplicates && in_array($vid, $existingIds, true)) {
                    $skipped++;
                    continue;
                }

                $watchUrl = 'https://www.youtube.com/watch?v=' . $vid;
                $thumbUrl = trim((string) ($item['thumbnail'] ?? '')) ?: ('https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg');
                $savedImg = $this->downloadRemoteImage($thumbUrl, 'vid_', $storageType);
                if ($savedImg === '' && $thumbUrl !== ('https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg')) {
                    $savedImg = $this->downloadRemoteImage('https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg', 'vid_', $storageType);
                }

                $pubDate = !empty($item['published_at']) ? substr((string) $item['published_at'], 0, 10) : date('Y-m-d');

                $video = new Video();
                $video->type_id = $movieTypeId;
                $video->video_type = $videoType;
                $video->channel_id = 0;
                $video->producer_id = 0;
                $video->category_id = $catStr;
                $video->language_id = $langStr;
                $video->cast_id = '';
                $video->name = mb_substr(trim((string) ($item['title'] ?? 'YouTube Video ' . $vid)), 0, 250);
                $video->storage_type = $storageType;
                $video->thumbnail = $savedImg;
                $video->landscape = $savedImg;
                $video->description = trim((string) ($item['description'] ?? ''));
                $video->video_storage_type = $storageType;
                $video->video_upload_type = 'youtube';
                $video->video_320 = $watchUrl;
                $video->video_480 = $watchUrl;
                $video->video_720 = $watchUrl;
                $video->video_1080 = $watchUrl;
                $video->video_extension = '';
                $video->video_duration = max(0, (int) ($item['duration_ms'] ?? 0));
                $video->trailer_storage_type = $storageType;
                $video->trailer_type = 'youtube';
                $video->trailer_url = $watchUrl;
                $video->subtitle_storage_type = $storageType;
                $video->subtitle_type = 'external';
                $video->subtitle_lang_1 = '';
                $video->subtitle_1 = '';
                $video->subtitle_lang_2 = '';
                $video->subtitle_2 = '';
                $video->subtitle_lang_3 = '';
                $video->subtitle_3 = '';
                $video->release_date = $pubDate;
                $video->is_premium = $isPremium;
                $video->is_title = 1;
                $video->is_download = 0;
                $video->is_comment = 1;
                $video->is_like = 1;
                $video->is_rent = 0;
                $video->price = 0;
                $video->rent_day = 0;
                $video->avg_rating = 0;
                $video->total_review = 0;
                $video->total_view = 0;
                $video->total_like = 0;
                $video->status = 1;
                $video->save();

                $existingIds[] = $vid;
                $imported++;
            }

            return response()->json([
                'status'       => 200,
                'imported'     => $imported,
                'skipped'      => $skipped,
                'redirect_url' => route('admin.video.index', ['type_id' => $movieTypeId]),
                'success'      => "Imported {$imported} video(s) into Movies/Content" . ($skipped > 0 ? " ({$skipped} duplicate(s) skipped)." : '.'),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 400, 'errors' => $e->getMessage()]);
        }
    }

    // ------------------------------------------------------------------
    // URL Parsing & Fetching Helpers
    // ------------------------------------------------------------------

    private function parseYouTubeSource(string $input): array
    {
        $input = trim($input);

        // Bare Playlist ID
        if (preg_match('/^(PL|UU|OL|FL|RD)[A-Za-z0-9_-]{10,}$/', $input)) {
            return ['kind' => 'playlist', 'id' => $input, 'url' => 'https://www.youtube.com/playlist?list=' . $input];
        }

        // Bare Channel ID
        if (preg_match('/^UC[A-Za-z0-9_-]{20,}$/', $input)) {
            return ['kind' => 'channel_id', 'id' => $input, 'url' => 'https://www.youtube.com/channel/' . $input];
        }

        // Bare @handle
        if (preg_match('/^@[A-Za-z0-9_.-]+$/', $input)) {
            return ['kind' => 'channel_url', 'id' => $input, 'url' => 'https://www.youtube.com/' . $input];
        }

        if (!preg_match('#^https?://#i', $input)) {
            $input = 'https://' . $input;
        }

        $queryStr = parse_url($input, PHP_URL_QUERY) ?: '';
        parse_str($queryStr, $queryParams);
        if (!empty($queryParams['list'])) {
            return ['kind' => 'playlist', 'id' => $queryParams['list'], 'url' => $input];
        }

        $path = trim((string) (parse_url($input, PHP_URL_PATH) ?: ''), '/');
        if (preg_match('#^channel/(UC[A-Za-z0-9_-]+)#i', $path, $m)) {
            return ['kind' => 'channel_id', 'id' => $m[1], 'url' => $input];
        }

        return ['kind' => 'channel_url', 'id' => $path, 'url' => $input];
    }

    private function fetchViaApi(array $parsed, string $apiKey, int $limit, string $pageToken): array
    {
        $sourceTitle = 'YouTube';
        $sourceThumb = '';
        $playlistId = '';

        if ($parsed['kind'] === 'playlist') {
            $playlistId = $parsed['id'];
            $plResp = Http::timeout(12)->get('https://www.googleapis.com/youtube/v3/playlists', [
                'part' => 'snippet',
                'id'   => $playlistId,
                'key'  => $apiKey,
            ]);
            $plJson = $plResp->json();
            if (!empty($plJson['error']['message'])) {
                throw new Exception('YouTube API Error: ' . $plJson['error']['message']);
            }
            if (!empty($plJson['items'][0]['snippet'])) {
                $sn = $plJson['items'][0]['snippet'];
                $sourceTitle = $sn['title'] ?? $sourceTitle;
                $sourceThumb = $sn['thumbnails']['high']['url'] ?? ($sn['thumbnails']['medium']['url'] ?? '');
            }
        } else {
            $channelId = $parsed['kind'] === 'channel_id'
                ? $parsed['id']
                : $this->resolveChannelIdFromUrl($parsed['url'], $apiKey);

            if ($channelId === '') {
                throw new Exception('Could not resolve Channel ID from that URL. Try pasting the channel @handle or /channel/UC... URL.');
            }

            $chResp = Http::timeout(12)->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'snippet,contentDetails',
                'id'   => $channelId,
                'key'  => $apiKey,
            ]);
            $chJson = $chResp->json();
            if (!empty($chJson['error']['message'])) {
                throw new Exception('YouTube API Error: ' . $chJson['error']['message']);
            }
            if (empty($chJson['items'][0])) {
                throw new Exception('YouTube Channel not found.');
            }
            $chItem = $chJson['items'][0];
            $sourceTitle = $chItem['snippet']['title'] ?? $sourceTitle;
            $sourceThumb = $chItem['snippet']['thumbnails']['high']['url'] ?? '';
            $playlistId = $chItem['contentDetails']['relatedPlaylists']['uploads'] ?? ('UU' . substr($channelId, 2));
        }

        $videoIds = [];
        $token = $pageToken;
        $fetched = 0;

        do {
            $batchSize = min(50, $limit - $fetched);
            $params = [
                'part'       => 'snippet,contentDetails',
                'playlistId' => $playlistId,
                'maxResults' => $batchSize,
                'key'        => $apiKey,
            ];
            if ($token !== '') {
                $params['pageToken'] = $token;
            }

            $resp = Http::timeout(12)->get('https://www.googleapis.com/youtube/v3/playlistItems', $params);
            $json = $resp->json();
            if (!empty($json['error']['message'])) {
                throw new Exception('YouTube API Error: ' . $json['error']['message']);
            }

            foreach (($json['items'] ?? []) as $it) {
                $vid = $it['contentDetails']['videoId'] ?? ($it['snippet']['resourceId']['videoId'] ?? '');
                if ($vid !== '' && !in_array($vid, $videoIds, true)) {
                    $videoIds[] = $vid;
                    $fetched++;
                }
            }

            $token = $json['nextPageToken'] ?? '';
        } while ($token !== '' && $fetched < $limit);

        if (empty($videoIds)) {
            throw new Exception('No videos found in this YouTube channel or playlist.');
        }

        $videos = $this->fetchVideosDetailsViaApi($videoIds, $apiKey);

        return [
            'source' => [
                'title'       => $sourceTitle,
                'type'        => $parsed['kind'],
                'playlist_id' => $playlistId,
                'thumbnail'   => $sourceThumb ?: ($videos[0]['thumbnail'] ?? ''),
            ],
            'videos'          => $videos,
            'next_page_token' => $token,
        ];
    }

    private function fetchVideosDetailsViaApi(array $videoIds, string $apiKey): array
    {
        $videos = [];
        foreach (array_chunk($videoIds, 50) as $chunk) {
            $resp = Http::timeout(12)->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'snippet,contentDetails,status',
                'id'   => implode(',', $chunk),
                'key'  => $apiKey,
            ]);
            $json = $resp->json();
            if (!empty($json['error']['message'])) {
                throw new Exception('YouTube API Error: ' . $json['error']['message']);
            }

            foreach (($json['items'] ?? []) as $item) {
                $sn = $item['snippet'] ?? [];
                $isoDur = $item['contentDetails']['duration'] ?? 'PT0S';
                $durMs = $this->iso8601ToMs($isoDur);
                $thumb = $sn['thumbnails']['high']['url']
                    ?? ($sn['thumbnails']['medium']['url'] ?? ('https://i.ytimg.com/vi/' . $item['id'] . '/hqdefault.jpg'));

                $videos[] = [
                    'id'           => $item['id'],
                    'title'        => $sn['title'] ?? '',
                    'description'  => mb_substr((string) ($sn['description'] ?? ''), 0, 800),
                    'thumbnail'    => $thumb,
                    'duration_ms'  => $durMs,
                    'duration_str' => $this->msToLabel($durMs),
                    'published_at' => substr((string) ($sn['publishedAt'] ?? ''), 0, 10),
                    'embeddable'   => (bool) ($item['status']['embeddable'] ?? true),
                    'url'          => 'https://www.youtube.com/watch?v=' . $item['id'],
                ];
            }
        }
        return $videos;
    }

    private function fetchWithoutApiKey(array $parsed): array
    {
        $sourceTitle = 'YouTube';
        $sourceThumb = '';
        $videos = [];

        if ($parsed['kind'] === 'playlist') {
            $feedUrl = 'https://www.youtube.com/feeds/videos.xml?playlist_id=' . urlencode($parsed['id']);
            $parsedFeed = $this->parseRssFeed($feedUrl);
            $sourceTitle = $parsedFeed['title'] ?: 'YouTube Playlist';
            $videos = $parsedFeed['videos'];

            // Fallback / supplement from HTML playlist page
            if (count($videos) < 15) {
                $htmlVideos = $this->extractFromPlaylistHtml($parsed['id']);
                foreach ($htmlVideos as $hv) {
                    if (!$this->containsVideoId($videos, $hv['id'])) {
                        $videos[] = $hv;
                    }
                }
            }
        } else {
            $channelId = $parsed['kind'] === 'channel_id'
                ? $parsed['id']
                : $this->resolveChannelIdFromUrl($parsed['url'], '');

            if ($channelId === '') {
                throw new Exception('Could not resolve Channel ID from that URL. Try copying the channel URL with @handle or /channel/UC...');
            }

            $feedUrl = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . urlencode($channelId);
            $parsedFeed = $this->parseRssFeed($feedUrl);
            $sourceTitle = $parsedFeed['title'] ?: 'YouTube Channel';
            $videos = $parsedFeed['videos'];

            // Also try UU uploads playlist RSS/HTML if needed
            $uploadsPl = 'UU' . substr($channelId, 2);
            if (count($videos) < 15) {
                $plFeed = $this->parseRssFeed('https://www.youtube.com/feeds/videos.xml?playlist_id=' . urlencode($uploadsPl));
                foreach ($plFeed['videos'] as $pv) {
                    if (!$this->containsVideoId($videos, $pv['id'])) {
                        $videos[] = $pv;
                    }
                }
            }
        }

        if (empty($videos)) {
            throw new Exception('No public videos returned in Free Mode for this URL (the playlist/channel may be private or require a YouTube Data API v3 Key).');
        }

        $sourceThumb = $videos[0]['thumbnail'] ?? '';

        return [
            'source' => [
                'title'     => $sourceTitle,
                'type'      => $parsed['kind'],
                'thumbnail' => $sourceThumb,
            ],
            'videos'          => $videos,
            'next_page_token' => '',
        ];
    }

    private function resolveChannelIdFromUrl(string $url, string $apiKey): string
    {
        $path = trim((string) (parse_url($url, PHP_URL_PATH) ?: ''), '/');
        if ($apiKey !== '' && preg_match('#^@([A-Za-z0-9_.-]+)#', $path, $m)) {
            $resp = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/channels', [
                'part'      => 'id',
                'forHandle' => '@' . $m[1],
                'key'       => $apiKey,
            ]);
            $id = $resp->json('items.0.id');
            if (!empty($id)) {
                return (string) $id;
            }
        }

        $resp = Http::timeout(12)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36'])
            ->get($url);
        $html = $resp->body();

        if (preg_match('/"externalId":"(UC[A-Za-z0-9_-]{20,})"/', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/channel_id=(UC[A-Za-z0-9_-]{20,})/', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/"channelId":"(UC[A-Za-z0-9_-]{20,})"/', $html, $m)) {
            return $m[1];
        }
        return '';
    }

    private function parseRssFeed(string $feedUrl): array
    {
        $resp = Http::timeout(12)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get($feedUrl);

        if (!$resp->successful()) {
            return ['title' => '', 'videos' => []];
        }

        $xml = @simplexml_load_string($resp->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$xml) {
            return ['title' => '', 'videos' => []];
        }

        $channelTitle = (string) ($xml->title ?? '');
        $videos = [];

        foreach ($xml->entry as $entry) {
            $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');
            $media = $entry->children('http://search.yahoo.com/mrss/');
            $vid = (string) ($yt->videoId ?? '');
            if ($vid === '') {
                continue;
            }

            $desc = '';
            $thumb = 'https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg';
            if (isset($media->group)) {
                $desc = trim((string) ($media->group->description ?? ''));
                if (isset($media->group->thumbnail)) {
                    $attrs = $media->group->thumbnail->attributes();
                    if (!empty($attrs['url'])) {
                        $thumb = (string) $attrs['url'];
                    }
                }
            }

            $videos[] = [
                'id'           => $vid,
                'title'        => (string) ($entry->title ?? ''),
                'description'  => mb_substr($desc, 0, 800),
                'thumbnail'    => $thumb,
                'duration_ms'  => 0,
                'duration_str' => 'YouTube',
                'published_at' => substr((string) ($entry->published ?? ''), 0, 10),
                'embeddable'   => true,
                'url'          => 'https://www.youtube.com/watch?v=' . $vid,
            ];
        }

        return ['title' => $channelTitle, 'videos' => $videos];
    }

    private function extractFromPlaylistHtml(string $playlistId): array
    {
        $resp = Http::timeout(12)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36'])
            ->get('https://www.youtube.com/playlist?list=' . urlencode($playlistId));

        $html = $resp->body();
        if (!preg_match('/var ytInitialData = (\{.*?\});<\/script>/s', $html, $m)) {
            return [];
        }

        $data = json_decode($m[1], true);
        if (!is_array($data)) {
            return [];
        }

        $videos = [];
        $walk = function ($node) use (&$walk, &$videos) {
            if (!is_array($node)) {
                return;
            }
            if (!empty($node['playlistVideoRenderer']['videoId'])) {
                $v = $node['playlistVideoRenderer'];
                $vid = $v['videoId'];
                $title = $v['title']['runs'][0]['text'] ?? ($v['title']['simpleText'] ?? ('Video ' . $vid));
                $lenText = $v['lengthText']['simpleText'] ?? '';
                $durMs = $this->labelToMs($lenText);
                $videos[] = [
                    'id'           => $vid,
                    'title'        => $title,
                    'description'  => '',
                    'thumbnail'    => 'https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg',
                    'duration_ms'  => $durMs,
                    'duration_str' => $lenText ?: 'YouTube',
                    'published_at' => date('Y-m-d'),
                    'embeddable'   => true,
                    'url'          => 'https://www.youtube.com/watch?v=' . $vid,
                ];
                return;
            }
            foreach ($node as $child) {
                $walk($child);
            }
        };
        $walk($data);

        return $videos;
    }

    private function fetchVideosDetailsViaOembed(array $videoIds): array
    {
        $videos = [];
        foreach ($videoIds as $vid) {
            $watchUrl = 'https://www.youtube.com/watch?v=' . $vid;
            $title = 'YouTube Video (' . $vid . ')';
            $thumb = 'https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg';

            try {
                $resp = Http::timeout(8)->get('https://www.youtube.com/oembed', [
                    'url'    => $watchUrl,
                    'format' => 'json',
                ]);
                if ($resp->successful()) {
                    $json = $resp->json();
                    if (!empty($json['title'])) {
                        $title = $json['title'];
                    }
                    if (!empty($json['thumbnail_url'])) {
                        $thumb = $json['thumbnail_url'];
                    }
                }
            } catch (Exception $e) {
                // Ignore individual oembed failure and keep fallback thumbnail
            }

            $videos[] = [
                'id'           => $vid,
                'title'        => $title,
                'description'  => '',
                'thumbnail'    => $thumb,
                'duration_ms'  => 0,
                'duration_str' => 'YouTube',
                'published_at' => date('Y-m-d'),
                'embeddable'   => true,
                'url'          => $watchUrl,
            ];
        }
        return $videos;
    }

    private function attachExistingFlags(array $videos): array
    {
        $epIds = $this->extractVideoIdsFromArray(
            TVShow_Video::where('video_upload_type', 'youtube')->pluck('video_320')->toArray()
        );
        $vidIds = $this->extractVideoIdsFromArray(
            Video::where('video_upload_type', 'youtube')->pluck('video_320')->toArray()
        );

        foreach ($videos as &$v) {
            $v['exists_in_show'] = in_array($v['id'], $epIds, true);
            $v['exists_in_movie'] = in_array($v['id'], $vidIds, true);
        }
        return $videos;
    }

    private function extractVideoIdsFromText(string $text): array
    {
        $lines = preg_split('/[\r\n,\s]+/', trim($text));
        $ids = [];
        foreach ($lines as $token) {
            $id = $this->extractSingleVideoId(trim($token));
            if ($id !== '' && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    private function extractVideoIdsFromArray(array $urls): array
    {
        $ids = [];
        foreach ($urls as $u) {
            $id = $this->extractSingleVideoId((string) $u);
            if ($id !== '') {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function extractSingleVideoId(string $url): string
    {
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m)) {
            return $m[1];
        }
        return '';
    }

    private function containsVideoId(array $videos, string $id): bool
    {
        foreach ($videos as $v) {
            if (($v['id'] ?? '') === $id) {
                return true;
            }
        }
        return false;
    }

    private function downloadRemoteImage(string $url, string $prefix, int $storageType): string
    {
        try {
            $resp = Http::timeout(8)->get($url);
            if (!$resp->successful() || strlen($resp->body()) < 500) {
                return '';
            }

            $filename = $prefix . date('Y_m_d_') . uniqid() . '.jpg';
            if ($storageType === 1) {
                Storage::disk('public')->put($this->folder . '/' . $filename, $resp->body());
            } elseif ($storageType === 2) {
                Storage::disk('s3')->put($this->folder . '/' . $filename, $resp->body(), 'public');
            } elseif ($storageType === 3) {
                Storage::disk('wasabi')->put($this->folder . '/' . $filename, $resp->body(), 'public');
            }
            return $filename;
        } catch (Exception $e) {
            return '';
        }
    }

    private function iso8601ToMs(string $iso): int
    {
        if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $iso, $m)) {
            $h = (int) ($m[1] ?? 0);
            $min = (int) ($m[2] ?? 0);
            $s = (int) ($m[3] ?? 0);
            return (($h * 3600) + ($min * 60) + $s) * 1000;
        }
        return 0;
    }

    private function msToLabel(int $ms): string
    {
        $sec = (int) floor($ms / 1000);
        if ($sec <= 0) {
            return 'YouTube';
        }
        $h = (int) floor($sec / 3600);
        $m = (int) floor(($sec % 3600) / 60);
        $s = $sec % 60;
        return $h > 0
            ? sprintf('%d:%02d:%02d', $h, $m, $s)
            : sprintf('%02d:%02d', $m, $s);
    }

    private function labelToMs(string $label): int
    {
        $parts = array_reverse(explode(':', trim($label)));
        $sec = 0;
        foreach ($parts as $idx => $p) {
            $sec += ((int) $p) * (60 ** $idx);
        }
        return $sec * 1000;
    }
}
