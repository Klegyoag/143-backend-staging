# 143 API Catalog

**351 HTTP operations · 265 distinct paths · 53 realtime events**

All paths below are prefixed by `/api/v1`.

## 1. App Launch, Onboarding & Legal

5 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/bootstrap` | `get_bootstrap` | Public |
| `GET` | `/legal/documents` | `get_legal_documents` | Public |
| `GET` | `/legal/documents/{type}` | `get_legal_documents_type` | Public |
| `GET` | `/consents` | `get_consents` | Bearer |
| `POST` | `/consents` | `post_consents` | Bearer |

## 2. Authentication & Account Access

11 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `POST` | `/auth/register` | `post_auth_register` | Public |
| `POST` | `/auth/login` | `post_auth_login` | Public |
| `POST` | `/auth/recovery/confirm` | `post_auth_recovery_confirm` | Bearer |
| `GET` | `/auth/recovery/status` | `get_auth_recovery_status` | Bearer |
| `POST` | `/auth/password/reset/recovery-code` | `post_auth_password_reset_recovery_code` | Public |
| `POST` | `/auth/recovery-code/regenerate` | `post_auth_recovery_code_regenerate` | Bearer |
| `POST` | `/auth/password/change` | `post_auth_password_change` | Bearer |
| `POST` | `/auth/token/refresh` | `post_auth_token_refresh` | Public |
| `POST` | `/auth/logout` | `post_auth_logout` | Bearer |
| `POST` | `/auth/logout-all` | `post_auth_logout_all` | Bearer |
| `DELETE` | `/account` | `delete_account` | Bearer |

## 3. Couple Space, Partner Linking & Relationship

15 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `POST` | `/couple-spaces` | `post_couple_spaces` | Bearer |
| `GET` | `/couple-spaces/current` | `get_couple_spaces_current` | Bearer |
| `POST` | `/couple-spaces/current/invites` | `post_couple_spaces_current_invites` | Bearer |
| `GET` | `/couple-invites/{token}/preview` | `get_couple_invites_token_preview` | Public |
| `POST` | `/couple-invites/{token}/accept` | `post_couple_invites_token_accept` | Bearer |
| `POST` | `/couple-invites/{inviteId}/revoke` | `post_couple_invites_inviteid_revoke` | Bearer |
| `POST` | `/couple-invites/{inviteId}/regenerate` | `post_couple_invites_inviteid_regenerate` | Bearer |
| `POST` | `/couple-spaces/join/code` | `post_couple_spaces_join_code` | Bearer |
| `DELETE` | `/couple-spaces/current/membership` | `delete_couple_spaces_current_membership` | Bearer |
| `GET` | `/couple-spaces/current/relationship` | `get_couple_spaces_current_relationship` | Bearer |
| `PATCH` | `/couple-spaces/current/relationship` | `patch_couple_spaces_current_relationship` | Bearer |
| `GET` | `/couple-spaces/current/settings` | `get_couple_spaces_current_settings` | Bearer |
| `PATCH` | `/couple-spaces/current/settings` | `patch_couple_spaces_current_settings` | Bearer |
| `GET` | `/couple-spaces/current/presence` | `get_couple_spaces_current_presence` | Bearer |
| `PUT` | `/presence` | `put_presence` | Bearer |

## 4. Home, Navigation & Discovery

7 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/home` | `get_home` | Bearer |
| `GET` | `/search` | `get_search` | Bearer |
| `GET` | `/activity` | `get_activity` | Bearer |
| `GET` | `/favorites` | `get_favorites` | Bearer |
| `POST` | `/favorites` | `post_favorites` | Bearer |
| `DELETE` | `/favorites/{favoriteId}` | `delete_favorites_favoriteid` | Bearer |
| `GET` | `/unread-summary` | `get_unread_summary` | Bearer |

## 5. Memories & Shared Content

17 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/memories` | `get_memories` | Bearer |
| `POST` | `/memories` | `post_memories` | Bearer |
| `GET` | `/memories/{memoryId}` | `get_memories_memoryid` | Bearer |
| `PATCH` | `/memories/{memoryId}` | `patch_memories_memoryid` | Bearer |
| `DELETE` | `/memories/{memoryId}` | `delete_memories_memoryid` | Bearer |
| `GET` | `/memories/drafts` | `get_memories_drafts` | Bearer |
| `GET` | `/memories/{memoryId}/comments` | `get_memories_memoryid_comments` | Bearer |
| `POST` | `/memories/{memoryId}/comments` | `post_memories_memoryid_comments` | Bearer |
| `PATCH` | `/comments/{commentId}` | `patch_comments_commentid` | Bearer |
| `DELETE` | `/comments/{commentId}` | `delete_comments_commentid` | Bearer |
| `POST` | `/memories/{memoryId}/reactions` | `post_memories_memoryid_reactions` | Bearer |
| `DELETE` | `/memories/{memoryId}/reactions/{reaction}` | `delete_memories_memoryid_reactions_reaction` | Bearer |
| `POST` | `/memories/{memoryId}/share` | `post_memories_memoryid_share` | Bearer |
| `POST` | `/time-capsules/{memoryId}/lock` | `post_time_capsules_memoryid_lock` | Bearer |
| `POST` | `/time-capsules/{memoryId}/schedule` | `post_time_capsules_memoryid_schedule` | Bearer |
| `POST` | `/time-capsules/{memoryId}/reveal` | `post_time_capsules_memoryid_reveal` | Bearer |
| `GET` | `/time-capsules/{memoryId}/status` | `get_time_capsules_memoryid_status` | Bearer |

## 6. Chat & Messaging

11 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/conversations/couple` | `get_conversations_couple` | Bearer |
| `GET` | `/conversations/{conversationId}/messages` | `get_conversations_conversationid_messages` | Bearer |
| `POST` | `/conversations/{conversationId}/messages` | `post_conversations_conversationid_messages` | Bearer |
| `GET` | `/messages/{messageId}` | `get_messages_messageid` | Bearer |
| `PATCH` | `/messages/{messageId}` | `patch_messages_messageid` | Bearer |
| `DELETE` | `/messages/{messageId}` | `delete_messages_messageid` | Bearer |
| `POST` | `/messages/{messageId}/reactions` | `post_messages_messageid_reactions` | Bearer |
| `DELETE` | `/messages/{messageId}/reactions/{reaction}` | `delete_messages_messageid_reactions_reaction` | Bearer |
| `POST` | `/messages/{messageId}/read` | `post_messages_messageid_read` | Bearer |
| `POST` | `/conversations/{conversationId}/read` | `post_conversations_conversationid_read` | Bearer |
| `GET` | `/conversations/{conversationId}/unread-count` | `get_conversations_conversationid_unread_count` | Bearer |

## 7. Voice & Video Calling

6 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `POST` | `/calls` | `post_calls` | Bearer |
| `GET` | `/calls/{callId}` | `get_calls_callid` | Bearer |
| `POST` | `/calls/{callId}/accept` | `post_calls_callid_accept` | Bearer |
| `POST` | `/calls/{callId}/decline` | `post_calls_callid_decline` | Bearer |
| `POST` | `/calls/{callId}/end` | `post_calls_callid_end` | Bearer |
| `GET` | `/calls/ice-servers` | `get_calls_ice_servers` | Bearer |

## 8. Together Room

11 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `POST` | `/together-rooms` | `post_together_rooms` | Bearer |
| `GET` | `/together-rooms/current` | `get_together_rooms_current` | Bearer |
| `POST` | `/together-rooms/{roomId}/join` | `post_together_rooms_roomid_join` | Bearer |
| `POST` | `/together-rooms/{roomId}/leave` | `post_together_rooms_roomid_leave` | Bearer |
| `GET` | `/together-rooms/{roomId}/state` | `get_together_rooms_roomid_state` | Bearer |
| `PATCH` | `/together-rooms/{roomId}/state` | `patch_together_rooms_roomid_state` | Bearer |
| `GET` | `/together-rooms/{roomId}/queue` | `get_together_rooms_roomid_queue` | Bearer |
| `POST` | `/together-rooms/{roomId}/queue` | `post_together_rooms_roomid_queue` | Bearer |
| `PATCH` | `/together-rooms/{roomId}/queue/reorder` | `patch_together_rooms_roomid_queue_reorder` | Bearer |
| `DELETE` | `/together-rooms/{roomId}/queue/{itemId}` | `delete_together_rooms_roomid_queue_itemid` | Bearer |
| `POST` | `/together-rooms/{roomId}/resync` | `post_together_rooms_roomid_resync` | Bearer |

## 9. Romantic Interaction & Couple Engagement

12 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `POST` | `/pings` | `post_pings` | Bearer |
| `GET` | `/pings` | `get_pings` | Bearer |
| `POST` | `/pings/{pingId}/reactions` | `post_pings_pingid_reactions` | Bearer |
| `GET` | `/reminders` | `get_reminders` | Bearer |
| `POST` | `/reminders` | `post_reminders` | Bearer |
| `PATCH` | `/reminders/{reminderId}` | `patch_reminders_reminderid` | Bearer |
| `DELETE` | `/reminders/{reminderId}` | `delete_reminders_reminderid` | Bearer |
| `GET` | `/check-ins` | `get_check_ins` | Bearer |
| `POST` | `/check-ins` | `post_check_ins` | Bearer |
| `GET` | `/streaks` | `get_streaks` | Bearer |
| `GET` | `/date-plans` | `get_date_plans` | Bearer |
| `POST` | `/date-plans` | `post_date_plans` | Bearer |

## 10. Notifications

8 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/notifications` | `get_notifications` | Bearer |
| `GET` | `/notifications/unread-count` | `get_notifications_unread_count` | Bearer |
| `POST` | `/notifications/{notificationId}/read` | `post_notifications_notificationid_read` | Bearer |
| `POST` | `/notifications/read-all` | `post_notifications_read_all` | Bearer |
| `GET` | `/notification-preferences` | `get_notification_preferences` | Bearer |
| `PUT` | `/notification-preferences` | `put_notification_preferences` | Bearer |
| `POST` | `/push-devices` | `post_push_devices` | Bearer |
| `DELETE` | `/push-devices/{deviceId}` | `delete_push_devices_deviceid` | Bearer |

## 11. Profile, Personalization, Appearance & Accessibility

6 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/me` | `get_me` | Bearer |
| `PATCH` | `/me` | `patch_me` | Bearer |
| `POST` | `/me/photo` | `post_me_photo` | Bearer |
| `DELETE` | `/me/photo` | `delete_me_photo` | Bearer |
| `GET` | `/me/preferences` | `get_me_preferences` | Bearer |
| `PATCH` | `/me/preferences` | `patch_me_preferences` | Bearer |

## 12. Privacy, Security, Sessions & Linked Devices

15 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/security/settings` | `get_security_settings` | Bearer |
| `PATCH` | `/security/settings` | `patch_security_settings` | Bearer |
| `GET` | `/sessions` | `get_sessions` | Bearer |
| `DELETE` | `/sessions/{sessionId}` | `delete_sessions_sessionid` | Bearer |
| `DELETE` | `/sessions` | `delete_sessions` | Bearer |
| `GET` | `/linked-devices` | `get_linked_devices` | Bearer |
| `POST` | `/linked-devices/link-intents` | `post_linked_devices_link_intents` | Bearer |
| `GET` | `/linked-devices/link-intents/{intentId}` | `get_linked_devices_link_intents_intentid` | Bearer |
| `POST` | `/linked-devices/link-intents/{intentId}/confirm` | `post_linked_devices_link_intents_intentid_confirm` | Bearer |
| `DELETE` | `/linked-devices/{deviceId}` | `delete_linked_devices_deviceid` | Bearer |
| `GET` | `/privacy/permissions` | `get_privacy_permissions` | Bearer |
| `PATCH` | `/privacy/permissions` | `patch_privacy_permissions` | Bearer |
| `POST` | `/blocks` | `post_blocks` | Bearer |
| `DELETE` | `/blocks/{userId}` | `delete_blocks_userid` | Bearer |
| `POST` | `/reports` | `post_reports` | Bearer |

## 13. Media, Files & Location

9 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `POST` | `/uploads/presign` | `post_uploads_presign` | Bearer |
| `POST` | `/uploads/complete` | `post_uploads_complete` | Bearer |
| `GET` | `/media/{mediaId}` | `get_media_mediaid` | Bearer |
| `DELETE` | `/media/{mediaId}` | `delete_media_mediaid` | Bearer |
| `POST` | `/location-shares` | `post_location_shares` | Bearer |
| `GET` | `/location-shares/{shareId}` | `get_location_shares_shareid` | Bearer |
| `PATCH` | `/location-shares/{shareId}` | `patch_location_shares_shareid` | Bearer |
| `DELETE` | `/location-shares/{shareId}` | `delete_location_shares_shareid` | Bearer |
| `GET` | `/location-shares/{shareId}/status` | `get_location_shares_shareid_status` | Bearer |

## 14. Subscription & Billing

10 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/subscription-plans` | `get_subscription_plans` | Bearer |
| `GET` | `/subscription` | `get_subscription` | Bearer |
| `POST` | `/subscription` | `post_subscription` | Bearer |
| `POST` | `/subscription/cancel` | `post_subscription_cancel` | Bearer |
| `POST` | `/subscription/renew` | `post_subscription_renew` | Bearer |
| `POST` | `/subscription/restore` | `post_subscription_restore` | Bearer |
| `POST` | `/subscription/sync` | `post_subscription_sync` | Bearer |
| `GET` | `/billing/history` | `get_billing_history` | Bearer |
| `GET` | `/billing/receipts/{receiptId}` | `get_billing_receipts_receiptid` | Bearer |
| `POST` | `/webhooks/paypal` | `post_webhooks_paypal` | Public |

## 15. Data, Support, Legal & About

11 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `POST` | `/exports` | `post_exports` | Bearer |
| `GET` | `/exports` | `get_exports` | Bearer |
| `GET` | `/exports/{exportId}` | `get_exports_exportid` | Bearer |
| `GET` | `/exports/{exportId}/download` | `get_exports_exportid_download` | Bearer |
| `GET` | `/help/articles` | `get_help_articles` | Bearer |
| `GET` | `/help/articles/{articleId}` | `get_help_articles_articleid` | Bearer |
| `POST` | `/support/tickets` | `post_support_tickets` | Bearer |
| `GET` | `/support/tickets` | `get_support_tickets` | Bearer |
| `POST` | `/feedback` | `post_feedback` | Bearer |
| `GET` | `/about` | `get_about` | Bearer |
| `GET` | `/release-notes` | `get_release_notes` | Bearer |

## 16. Realtime, Reliability & System UX

4 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/sync/state` | `get_sync_state` | Bearer |
| `POST` | `/sync/reconcile` | `post_sync_reconcile` | Bearer |
| `GET` | `/server-time` | `get_server_time` | Public |
| `GET` | `/health` | `get_health` | Public |

## 17. Daily Connection & Question Experience

7 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/daily/today` | `get_daily_today` | Bearer |
| `GET` | `/daily/questions/history` | `get_daily_questions_history` | Bearer |
| `POST` | `/daily/questions/{questionId}/answers` | `post_daily_questions_questionid_answers` | Bearer |
| `GET` | `/daily/questions/{questionId}/reveal` | `get_daily_questions_questionid_reveal` | Bearer |
| `POST` | `/daily/activities/{activityId}/complete` | `post_daily_activities_activityid_complete` | Bearer |
| `GET` | `/daily/answers` | `get_daily_answers` | Bearer |
| `GET` | `/daily/history` | `get_daily_history` | Bearer |

## 18. Couple Games & Play

10 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/games` | `get_games` | Bearer |
| `GET` | `/games/{gameKey}` | `get_games_gamekey` | Bearer |
| `POST` | `/game-sessions` | `post_game_sessions` | Bearer |
| `GET` | `/game-sessions/{sessionId}` | `get_game_sessions_sessionid` | Bearer |
| `POST` | `/game-sessions/{sessionId}/answers` | `post_game_sessions_sessionid_answers` | Bearer |
| `POST` | `/game-sessions/{sessionId}/complete` | `post_game_sessions_sessionid_complete` | Bearer |
| `GET` | `/game-sessions/{sessionId}/results` | `get_game_sessions_sessionid_results` | Bearer |
| `GET` | `/game-sessions` | `get_game_sessions` | Bearer |
| `GET` | `/xp` | `get_xp` | Bearer |
| `GET` | `/game-rewards` | `get_game_rewards` | Bearer |

## 19. Relationship Insights & Recaps

8 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/insights/overview` | `get_insights_overview` | Bearer |
| `GET` | `/insights/weekly` | `get_insights_weekly` | Bearer |
| `GET` | `/insights/monthly` | `get_insights_monthly` | Bearer |
| `GET` | `/insights/annual` | `get_insights_annual` | Bearer |
| `GET` | `/insights/streaks` | `get_insights_streaks` | Bearer |
| `GET` | `/insights/highlights` | `get_insights_highlights` | Bearer |
| `POST` | `/insights/share-cards` | `post_insights_share_cards` | Bearer |
| `PATCH` | `/insights/privacy` | `patch_insights_privacy` | Bearer |

## 20. Shared Couple Calendar & Special Dates

13 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/calendar/events` | `get_calendar_events` | Bearer |
| `POST` | `/calendar/events` | `post_calendar_events` | Bearer |
| `GET` | `/calendar/events/{eventId}` | `get_calendar_events_eventid` | Bearer |
| `PATCH` | `/calendar/events/{eventId}` | `patch_calendar_events_eventid` | Bearer |
| `DELETE` | `/calendar/events/{eventId}` | `delete_calendar_events_eventid` | Bearer |
| `GET` | `/calendar/events/{eventId}/comments` | `get_calendar_events_eventid_comments` | Bearer |
| `POST` | `/calendar/events/{eventId}/comments` | `post_calendar_events_eventid_comments` | Bearer |
| `POST` | `/calendar/events/{eventId}/photos` | `post_calendar_events_eventid_photos` | Bearer |
| `DELETE` | `/calendar/events/{eventId}/photos/{mediaId}` | `delete_calendar_events_eventid_photos_mediaid` | Bearer |
| `POST` | `/calendar/events/{eventId}/complete` | `post_calendar_events_eventid_complete` | Bearer |
| `POST` | `/calendar/events/{eventId}/convert-to-memory` | `post_calendar_events_eventid_convert_to_memory` | Bearer |
| `POST` | `/calendar/events/{eventId}/reflection` | `post_calendar_events_eventid_reflection` | Bearer |
| `GET` | `/countdowns` | `get_countdowns` | Bearer |

## 21. Long-Distance Couple Mode

13 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/long-distance` | `get_long_distance` | Bearer |
| `PATCH` | `/long-distance` | `patch_long_distance` | Bearer |
| `GET` | `/long-distance/partner-time` | `get_long_distance_partner_time` | Bearer |
| `GET` | `/long-distance/distance` | `get_long_distance_distance` | Bearer |
| `GET` | `/long-distance/next-meet` | `get_long_distance_next_meet` | Bearer |
| `PUT` | `/long-distance/next-meet` | `put_long_distance_next_meet` | Bearer |
| `GET` | `/travel-itineraries` | `get_travel_itineraries` | Bearer |
| `POST` | `/travel-itineraries` | `post_travel_itineraries` | Bearer |
| `GET` | `/travel-itineraries/{itineraryId}` | `get_travel_itineraries_itineraryid` | Bearer |
| `PATCH` | `/travel-itineraries/{itineraryId}` | `patch_travel_itineraries_itineraryid` | Bearer |
| `DELETE` | `/travel-itineraries/{itineraryId}` | `delete_travel_itineraries_itineraryid` | Bearer |
| `POST` | `/virtual-dates` | `post_virtual_dates` | Bearer |
| `GET` | `/virtual-dates/{virtualDateId}` | `get_virtual_dates_virtualdateid` | Bearer |

## 22. Couple Jars, Bucket List & Shared Goals

25 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/jars` | `get_jars` | Bearer |
| `POST` | `/jars` | `post_jars` | Bearer |
| `GET` | `/jars/{jarId}` | `get_jars_jarid` | Bearer |
| `PATCH` | `/jars/{jarId}` | `patch_jars_jarid` | Bearer |
| `DELETE` | `/jars/{jarId}` | `delete_jars_jarid` | Bearer |
| `GET` | `/jars/{jarId}/entries` | `get_jars_jarid_entries` | Bearer |
| `POST` | `/jars/{jarId}/entries` | `post_jars_jarid_entries` | Bearer |
| `PATCH` | `/jars/{jarId}/entries/{entryId}` | `patch_jars_jarid_entries_entryid` | Bearer |
| `DELETE` | `/jars/{jarId}/entries/{entryId}` | `delete_jars_jarid_entries_entryid` | Bearer |
| `POST` | `/jars/{jarId}/random-pick` | `post_jars_jarid_random_pick` | Bearer |
| `GET` | `/bucket-list` | `get_bucket_list` | Bearer |
| `POST` | `/bucket-list` | `post_bucket_list` | Bearer |
| `GET` | `/bucket-list/{itemId}` | `get_bucket_list_itemid` | Bearer |
| `PATCH` | `/bucket-list/{itemId}` | `patch_bucket_list_itemid` | Bearer |
| `DELETE` | `/bucket-list/{itemId}` | `delete_bucket_list_itemid` | Bearer |
| `POST` | `/bucket-list/{itemId}/comments` | `post_bucket_list_itemid_comments` | Bearer |
| `POST` | `/bucket-list/{itemId}/complete` | `post_bucket_list_itemid_complete` | Bearer |
| `POST` | `/bucket-list/{itemId}/convert-to-milestone` | `post_bucket_list_itemid_convert_to_milestone` | Bearer |
| `GET` | `/goals` | `get_goals` | Bearer |
| `POST` | `/goals` | `post_goals` | Bearer |
| `GET` | `/goals/{goalId}` | `get_goals_goalid` | Bearer |
| `PATCH` | `/goals/{goalId}` | `patch_goals_goalid` | Bearer |
| `DELETE` | `/goals/{goalId}` | `delete_goals_goalid` | Bearer |
| `POST` | `/goals/{goalId}/progress` | `post_goals_goalid_progress` | Bearer |
| `POST` | `/goals/{goalId}/comments` | `post_goals_goalid_comments` | Bearer |

## 23. Love Language & Partner Preferences

6 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/preferences` | `get_preferences` | Bearer |
| `PUT` | `/preferences` | `put_preferences` | Bearer |
| `GET` | `/partner/preferences` | `get_partner_preferences` | Bearer |
| `GET` | `/love-language` | `get_love_language` | Bearer |
| `PUT` | `/love-language` | `put_love_language` | Bearer |
| `GET` | `/romantic-suggestions` | `get_romantic_suggestions` | Bearer |

## 24. Mood, Need-You & Touch Connection

6 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/moods/current` | `get_moods_current` | Bearer |
| `POST` | `/moods` | `post_moods` | Bearer |
| `GET` | `/moods` | `get_moods` | Bearer |
| `PATCH` | `/moods/privacy` | `patch_moods_privacy` | Bearer |
| `POST` | `/need-you` | `post_need_you` | Bearer |
| `POST` | `/need-you/{needYouId}/respond` | `post_need_you_needyouid_respond` | Bearer |

## 25. Scheduled Love & Enhanced Surprise Mode

11 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/scheduled-messages` | `get_scheduled_messages` | Bearer |
| `POST` | `/scheduled-messages` | `post_scheduled_messages` | Bearer |
| `GET` | `/scheduled-messages/{scheduledMessageId}` | `get_scheduled_messages_scheduledmessageid` | Bearer |
| `PATCH` | `/scheduled-messages/{scheduledMessageId}` | `patch_scheduled_messages_scheduledmessageid` | Bearer |
| `DELETE` | `/scheduled-messages/{scheduledMessageId}` | `delete_scheduled_messages_scheduledmessageid` | Bearer |
| `GET` | `/surprises` | `get_surprises` | Bearer |
| `POST` | `/surprises` | `post_surprises` | Bearer |
| `GET` | `/surprises/{surpriseId}` | `get_surprises_surpriseid` | Bearer |
| `PATCH` | `/surprises/{surpriseId}` | `patch_surprises_surpriseid` | Bearer |
| `DELETE` | `/surprises/{surpriseId}` | `delete_surprises_surpriseid` | Bearer |
| `POST` | `/surprises/{surpriseId}/unlock` | `post_surprises_surpriseid_unlock` | Bearer |

## 26. Relationship Story & Collaborative Albums

15 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/story` | `get_story` | Bearer |
| `GET` | `/story/events` | `get_story_events` | Bearer |
| `POST` | `/story/events` | `post_story_events` | Bearer |
| `PATCH` | `/story/events/{storyEventId}` | `patch_story_events_storyeventid` | Bearer |
| `DELETE` | `/story/events/{storyEventId}` | `delete_story_events_storyeventid` | Bearer |
| `GET` | `/albums` | `get_albums` | Bearer |
| `POST` | `/albums` | `post_albums` | Bearer |
| `GET` | `/albums/{albumId}` | `get_albums_albumid` | Bearer |
| `PATCH` | `/albums/{albumId}` | `patch_albums_albumid` | Bearer |
| `DELETE` | `/albums/{albumId}` | `delete_albums_albumid` | Bearer |
| `POST` | `/albums/{albumId}/photos` | `post_albums_albumid_photos` | Bearer |
| `PATCH` | `/albums/{albumId}/photos/{photoId}` | `patch_albums_albumid_photos_photoid` | Bearer |
| `DELETE` | `/albums/{albumId}/photos/{photoId}` | `delete_albums_albumid_photos_photoid` | Bearer |
| `POST` | `/albums/{albumId}/lock` | `post_albums_albumid_lock` | Bearer |
| `POST` | `/albums/{albumId}/export` | `post_albums_albumid_export` | Bearer |

## 27. Music Identity & Couple-Space Customization

9 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/couple-profile` | `get_couple_profile` | Bearer |
| `PATCH` | `/couple-profile` | `patch_couple_profile` | Bearer |
| `POST` | `/integrations/spotify/connect` | `post_integrations_spotify_connect` | Bearer |
| `GET` | `/integrations/spotify/callback` | `get_integrations_spotify_callback` | Public |
| `DELETE` | `/integrations/spotify` | `delete_integrations_spotify` | Bearer |
| `GET` | `/integrations/spotify/playlists` | `get_integrations_spotify_playlists` | Bearer |
| `GET` | `/integrations/spotify/now-playing` | `get_integrations_spotify_now_playing` | Bearer |
| `GET` | `/couple-music` | `get_couple_music` | Bearer |
| `PUT` | `/couple-music` | `put_couple_music` | Bearer |

## 28. Home-Screen & Lock-Screen Widgets

4 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/widgets/config` | `get_widgets_config` | Bearer |
| `PUT` | `/widgets/config` | `put_widgets_config` | Bearer |
| `GET` | `/widgets/data` | `get_widgets_data` | Bearer |
| `POST` | `/widgets/ping` | `post_widgets_ping` | Bearer |

## 29. Advanced Privacy, Encryption, Backup & Migration

14 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `POST` | `/crypto/devices/keys` | `post_crypto_devices_keys` | Bearer |
| `PUT` | `/crypto/devices/keys/signed-prekey` | `put_crypto_devices_keys_signed_prekey` | Bearer |
| `POST` | `/crypto/devices/keys/one-time-prekeys` | `post_crypto_devices_keys_one_time_prekeys` | Bearer |
| `GET` | `/crypto/users/{userId}/key-bundle` | `get_crypto_users_userid_key_bundle` | Bearer |
| `GET` | `/crypto/devices/keys/status` | `get_crypto_devices_keys_status` | Bearer |
| `GET` | `/backups` | `get_backups` | Bearer |
| `POST` | `/backups` | `post_backups` | Bearer |
| `GET` | `/backups/{backupId}` | `get_backups_backupid` | Bearer |
| `DELETE` | `/backups/{backupId}` | `delete_backups_backupid` | Bearer |
| `POST` | `/backups/{backupId}/restore` | `post_backups_backupid_restore` | Bearer |
| `GET` | `/backups/status` | `get_backups_status` | Bearer |
| `POST` | `/migrations/device/prepare` | `post_migrations_device_prepare` | Bearer |
| `POST` | `/migrations/device/confirm` | `post_migrations_device_confirm` | Bearer |
| `GET` | `/migrations/device/{migrationId}/status` | `get_migrations_device_migrationid_status` | Bearer |

## 30. Conflict Cool-Down & Guided Reconnection

8 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/cool-down/current` | `get_cool_down_current` | Bearer |
| `POST` | `/cool-down` | `post_cool_down` | Bearer |
| `PATCH` | `/cool-down/{coolDownId}` | `patch_cool_down_cooldownid` | Bearer |
| `DELETE` | `/cool-down/{coolDownId}` | `delete_cool_down_cooldownid` | Bearer |
| `GET` | `/reconnection/prompts` | `get_reconnection_prompts` | Bearer |
| `POST` | `/reconnection/sessions` | `post_reconnection_sessions` | Bearer |
| `POST` | `/reconnection/sessions/{sessionId}/answers` | `post_reconnection_sessions_sessionid_answers` | Bearer |
| `GET` | `/reconnection/sessions/{sessionId}/reveal` | `get_reconnection_sessions_sessionid_reveal` | Bearer |

## 31. Guided Couple Journeys & Challenges

7 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/journeys` | `get_journeys` | Bearer |
| `GET` | `/journeys/{journeyId}` | `get_journeys_journeyid` | Bearer |
| `POST` | `/journeys/{journeyId}/start` | `post_journeys_journeyid_start` | Bearer |
| `GET` | `/journey-sessions` | `get_journey_sessions` | Bearer |
| `GET` | `/journey-sessions/{sessionId}` | `get_journey_sessions_sessionid` | Bearer |
| `POST` | `/journey-sessions/{sessionId}/steps/{stepId}/complete` | `post_journey_sessions_sessionid_steps_stepid_complete` | Bearer |
| `POST` | `/journey-sessions/{sessionId}/complete` | `post_journey_sessions_sessionid_complete` | Bearer |

## 32. Privacy-First 143 AI Assistant

11 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/ai/settings` | `get_ai_settings` | Bearer |
| `PUT` | `/ai/settings` | `put_ai_settings` | Bearer |
| `POST` | `/ai/suggestions/date` | `post_ai_suggestions_date` | Bearer |
| `POST` | `/ai/suggestions/love-note` | `post_ai_suggestions_love_note` | Bearer |
| `POST` | `/ai/suggestions/memory-caption` | `post_ai_suggestions_memory_caption` | Bearer |
| `POST` | `/ai/suggestions/anniversary` | `post_ai_suggestions_anniversary` | Bearer |
| `POST` | `/ai/suggestions/gift` | `post_ai_suggestions_gift` | Bearer |
| `POST` | `/ai/suggestions/conversation` | `post_ai_suggestions_conversation` | Bearer |
| `POST` | `/ai/suggestions/bucket-list` | `post_ai_suggestions_bucket_list` | Bearer |
| `POST` | `/ai/suggestions/trip` | `post_ai_suggestions_trip` | Bearer |
| `POST` | `/ai/suggestions/game` | `post_ai_suggestions_game` | Bearer |

## 33. Shared Wishlist & Shared Media Lists

9 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/shared-lists` | `get_shared_lists` | Bearer |
| `POST` | `/shared-lists` | `post_shared_lists` | Bearer |
| `GET` | `/shared-lists/{listId}` | `get_shared_lists_listid` | Bearer |
| `PATCH` | `/shared-lists/{listId}` | `patch_shared_lists_listid` | Bearer |
| `DELETE` | `/shared-lists/{listId}` | `delete_shared_lists_listid` | Bearer |
| `GET` | `/shared-lists/{listId}/items` | `get_shared_lists_listid_items` | Bearer |
| `POST` | `/shared-lists/{listId}/items` | `post_shared_lists_listid_items` | Bearer |
| `PATCH` | `/shared-lists/{listId}/items/{itemId}` | `patch_shared_lists_listid_items_itemid` | Bearer |
| `DELETE` | `/shared-lists/{listId}/items/{itemId}` | `delete_shared_lists_listid_items_itemid` | Bearer |

## 34. Smart Date Generator

2 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `POST` | `/date-generator/suggestions` | `post_date_generator_suggestions` | Bearer |
| `GET` | `/date-generator/history` | `get_date_generator_history` | Bearer |

## 35. Optional Couple Budget & Expenses

12 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/budgets` | `get_budgets` | Bearer |
| `POST` | `/budgets` | `post_budgets` | Bearer |
| `GET` | `/budgets/{budgetId}` | `get_budgets_budgetid` | Bearer |
| `PATCH` | `/budgets/{budgetId}` | `patch_budgets_budgetid` | Bearer |
| `DELETE` | `/budgets/{budgetId}` | `delete_budgets_budgetid` | Bearer |
| `GET` | `/expenses` | `get_expenses` | Bearer |
| `POST` | `/expenses` | `post_expenses` | Bearer |
| `PATCH` | `/expenses/{expenseId}` | `patch_expenses_expenseid` | Bearer |
| `DELETE` | `/expenses/{expenseId}` | `delete_expenses_expenseid` | Bearer |
| `POST` | `/expenses/{expenseId}/split` | `post_expenses_expenseid_split` | Bearer |
| `GET` | `/budget-privacy` | `get_budget_privacy` | Bearer |
| `PUT` | `/budget-privacy` | `put_budget_privacy` | Bearer |

## 36. Achievements & Connection Rewards

3 HTTP operations

| Method | Path | Operation ID | Auth |
|---|---|---|---|
| `GET` | `/achievements` | `get_achievements` | Bearer |
| `GET` | `/achievements/{achievementId}` | `get_achievements_achievementid` | Bearer |
| `GET` | `/achievement-progress` | `get_achievement_progress` | Bearer |

