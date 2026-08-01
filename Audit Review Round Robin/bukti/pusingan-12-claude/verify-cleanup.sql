-- Pusingan 12 Claude: pengesahan read-only sahaja (tiada kolum token/email dipilih)
SELECT 'tokens_222_241_total' AS k, count(*) AS v FROM login_tokens WHERE id BETWEEN 222 AND 241
UNION ALL
SELECT 'tokens_222_241_used', count(*) FROM login_tokens WHERE id BETWEEN 222 AND 241 AND used_at IS NOT NULL
UNION ALL
SELECT 'tokens_222_241_active', count(*) FROM login_tokens WHERE id BETWEEN 222 AND 241 AND used_at IS NULL AND expires_at > now()
UNION ALL
SELECT 'tokens_222_235_expired_unused', count(*) FROM login_tokens WHERE id BETWEEN 222 AND 235 AND used_at IS NULL AND expires_at <= now()
UNION ALL
SELECT 'tokens_all_active_now', count(*) FROM login_tokens WHERE used_at IS NULL AND expires_at > now()
UNION ALL
SELECT 'tokens_created_20260801', count(*) FROM login_tokens WHERE created_at >= '2026-08-01 00:00:00' AND created_at < '2026-08-02 00:00:00'
UNION ALL
SELECT 'help_events_20260801', count(*) FROM help_events WHERE created_at >= '2026-08-01 00:00:00' AND created_at < '2026-08-02 00:00:00'
UNION ALL
SELECT 'help_events_started', count(*) FROM help_events WHERE created_at >= '2026-08-01 00:00:00' AND created_at < '2026-08-02 00:00:00' AND event = 'started'
UNION ALL
SELECT 'help_events_completed', count(*) FROM help_events WHERE created_at >= '2026-08-01 00:00:00' AND created_at < '2026-08-02 00:00:00' AND event = 'completed'
UNION ALL
SELECT 'help_events_dismissed', count(*) FROM help_events WHERE created_at >= '2026-08-01 00:00:00' AND created_at < '2026-08-02 00:00:00' AND event = 'dismissed'
UNION ALL
SELECT 'guidance_progress_updated_20260801', count(*) FROM guidance_progress WHERE updated_at >= '2026-08-01 00:00:00' AND updated_at < '2026-08-02 00:00:00';
