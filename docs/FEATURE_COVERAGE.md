# 143 Feature Coverage

The source specification defines **514 mobile capabilities across 36 modules**. This backend package preserves that source baseline while applying the explicitly approved authentication override: username + password only, with mandatory one-time recovery-code setup/confirmation.

| Module | Source capabilities | HTTP operations |
|---|---:|---:|
| 1. App Launch, Onboarding & Legal | 13 | 5 |
| 2. Authentication & Account Access | 17 | 11 |
| 3. Couple Space, Partner Linking & Relationship | 20 | 15 |
| 4. Home, Navigation & Discovery | 14 | 7 |
| 5. Memories & Shared Content | 26 | 17 |
| 6. Chat & Messaging | 22 | 11 |
| 7. Voice & Video Calling | 10 | 6 |
| 8. Together Room | 11 | 11 |
| 9. Romantic Interaction & Couple Engagement | 12 | 12 |
| 10. Notifications | 13 | 8 |
| 11. Profile, Personalization, Appearance & Accessibility | 21 | 6 |
| 12. Privacy, Security, Sessions & Linked Devices | 22 | 15 |
| 13. Media, Files & Location | 12 | 9 |
| 14. Subscription & Billing | 15 | 10 |
| 15. Data, Support, Legal & About | 14 | 11 |
| 16. Realtime, Reliability & System UX | 17 | 4 |
| 17. Daily Connection & Question Experience | 11 | 7 |
| 18. Couple Games & Play | 18 | 10 |
| 19. Relationship Insights & Recaps | 15 | 8 |
| 20. Shared Couple Calendar & Special Dates | 16 | 13 |
| 21. Long-Distance Couple Mode | 17 | 13 |
| 22. Couple Jars, Bucket List & Shared Goals | 20 | 25 |
| 23. Love Language & Partner Preferences | 12 | 6 |
| 24. Mood, Need-You & Touch Connection | 10 | 6 |
| 25. Scheduled Love & Enhanced Surprise Mode | 11 | 11 |
| 26. Relationship Story & Collaborative Albums | 17 | 15 |
| 27. Music Identity & Couple-Space Customization | 16 | 9 |
| 28. Home-Screen & Lock-Screen Widgets | 8 | 4 |
| 29. Advanced Privacy, Encryption, Backup & Migration | 13 | 14 |
| 30. Conflict Cool-Down & Guided Reconnection | 11 | 8 |
| 31. Guided Couple Journeys & Challenges | 10 | 7 |
| 32. Privacy-First 143 AI Assistant | 13 | 11 |
| 33. Shared Wishlist & Shared Media Lists | 14 | 9 |
| 34. Smart Date Generator | 6 | 2 |
| 35. Optional Couple Budget & Expenses | 6 | 12 |
| 36. Achievements & Connection Rewards | 11 | 3 |

**Source capability total:** 514
**Backend HTTP operations:** 351
**Distinct HTTP paths:** 265
**Realtime event contracts:** 53

Device-only capabilities such as biometric verification, haptic rendering, camera/gallery UX, accessibility semantics, and encrypted local caching are implemented by the Android/iOS client and therefore do not receive dedicated HTTP endpoints.
