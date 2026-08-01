-- Pusingan 12: butiran tanpa rahsia (tiada token, tiada email)
SELECT now() AS db_now, current_setting('TimeZone') AS tz;
SELECT id, created_at, expires_at, (used_at IS NOT NULL) AS used, intent
FROM login_tokens WHERE id BETWEEN 222 AND 241 ORDER BY id;
-- token lain dicipta 2026-08-01 di luar julat 222-241
SELECT id, created_at, expires_at, (used_at IS NOT NULL) AS used, intent
FROM login_tokens
WHERE created_at >= '2026-08-01 00:00:00' AND created_at < '2026-08-02 00:00:00'
  AND id NOT BETWEEN 222 AND 241
ORDER BY id;
-- taburan intent token aktif keseluruhan
SELECT intent, count(*) FROM login_tokens
WHERE used_at IS NULL AND expires_at > now() GROUP BY intent;
