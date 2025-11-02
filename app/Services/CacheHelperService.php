<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CacheHelperService
{
    public static function clearPostCaches($postId = null, $userId = null)
    {
        try {
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                self::clearRedisCachePatterns([
                    'posts_filtered_*',
                    'search_posts_*',
                ]);
            } else {
                self::clearCacheByPattern([
                    'posts_filtered_',
                    'search_posts_',
                ]);
            }
            
            if ($userId) {
                self::clearUserPostCaches($userId);
            }
            
        } catch (\Exception $e) {
            Log::warning('Failed to clear post caches: ' . $e->getMessage());
        }
    }

    public static function clearUserCaches($userId = null)
    {
        try {
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                self::clearRedisCachePatterns([
                    'search_users_*',
                    'search_followers_*',
                    'search_following_*',
                    'posts_filtered_' . ($userId ? $userId . '_*' : '*'),
                    'search_posts_*',
                ]);
            } else {
                self::clearCacheByPattern([
                    'search_users_',
                    'search_followers_',
                    'search_following_',
                    'posts_filtered_' . ($userId ? $userId . '_' : ''),
                    'search_posts_',
                ]);
            }
            
        } catch (\Exception $e) {
            Log::warning('Failed to clear user caches: ' . $e->getMessage());
        }
    }

    public static function clearUserPostCaches($userId)
    {
        try {
            $driver = config('cache.default');
            $prefix = config('cache.prefix', '');
            
            if ($driver === 'redis') {
                $pattern = $prefix . 'posts_filtered_' . $userId . '_*';
                self::clearRedisCachePatterns([$pattern]);
            } else {
                Cache::flush();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear user post caches: ' . $e->getMessage());
        }
    }

    private static function clearRedisCachePatterns(array $patterns)
    {
        try {
            $prefix = config('cache.prefix', '');
            $connection = config('cache.stores.redis.connection', 'cache');
            
            $redis = Redis::connection($connection);
            
            foreach ($patterns as $pattern) {
                $fullPattern = $prefix . $pattern;
                $keys = $redis->keys($fullPattern);
                
                if (!empty($keys)) {
                    if (is_array($keys)) {
                        foreach (array_chunk($keys, 1000) as $chunk) {
                            $redis->del($chunk);
                        }
                    } else {
                        $redis->del($keys);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear Redis cache patterns: ' . $e->getMessage());
        }
    }

    private static function clearCacheByPattern(array $patterns)
    {
        try {
            $driver = config('cache.default');
            
            if ($driver === 'file' || $driver === 'database') {
                Cache::flush();
            } else {
                foreach ($patterns as $pattern) {
                    if (method_exists(Cache::store(), 'forget')) {
                        Cache::forget($pattern);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear cache by pattern: ' . $e->getMessage());
            Cache::flush();
        }
    }

    public static function clearAllSearchCaches()
    {
        try {
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                self::clearRedisCachePatterns([
                    'search_posts_*',
                    'search_users_*',
                    'search_followers_*',
                    'search_following_*',
                ]);
            } else {
                Cache::flush();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear search caches: ' . $e->getMessage());
        }
    }
}

