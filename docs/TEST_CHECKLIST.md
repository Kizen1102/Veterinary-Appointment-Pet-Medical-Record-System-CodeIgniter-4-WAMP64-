# PawRecord Test Checklist

Manual tests to run before a demo or defense. Use **three browsers / windows** (for example Chrome, Edge and an Incognito window)
so the owner, the vet and the admin can stay signed in at the same time.

Mark each row ✅ or ❌, and write what happened in "Notes" when it fails.

## 0. Before testing
| # | Check | Expected | ✅/❌ | Notes |
|---|---|---|---|---|
| 0.1 | `vendor\bin\phpunit` | `OK (40 tests, ...)` | | |
| 0.2 | `php spark migrate:status` | Every migration has a date in "Migrated On" | | |
| 0.3 | Open `/system-check` | PHP 8.2+, extensions ✔, database connected, all tables and views ✔ | | |
| 0.4 | Fresh database: open `/setup` | Form to create the Clinic Staff account; after saving, the page is locked | | |
| 0.5 | (Demo) `php spark db:seed DemoSeeder`, then run it again | First: "Demo data added"; second: "already loaded". Sign in as `ana@pawrecord.test` / `demo1234` | | |

## 1. Accounts and security
| # | Do | Expected | ✅/❌ | Notes |
|---|---|---|---|---|
| 1.1 | Sign up as an owner with a 7-character password | ❌ "must be at least 8 characters" | | |
| 1.2 | Sign up with an e-mail already used | ❌ "already registered" | | |
| 1.3 | Sign up correctly | Lands on the owner home page | | |
| 1.4 | Log in with a wrong password 6 times quickly | Blocked for a minute after 5 tries | | |
| 1.5 | Forgot password → reset with the link → log in with the new password | Works; the old link no longer works | | |
| 1.6 | While signed out, open `/owner`, `/vet`, `/admin` | Sent to the login page | | |
| 1.7 | As owner open `/vet/patients` and `/admin/users`; as vet open `/admin/users` | Sent back to own home with "not allowed" | | |
| 1.8 | As owner, open `/pets/<id of another owner's pet>/edit` | Not found / not allowed | | |
| 1.9 | Type `<b>hi</b>` as a pet name | Shown as text, not bold | | |
| 1.10 | Log out | Back to the login page; opening `/owner` again asks to sign in | | |

## 2. Admin
| # | Do | Expected | ✅/❌ | Notes |
|---|---|---|---|---|
| 2.1 | **+ Add vet** (with license no.) | Account created; appears under Vets | | |
| 2.2 | Add staff with a used e-mail / passwords that don't match | Both errors shown; typed values kept | | |
| 2.3 | Sign in as the new vet | Lands on `/vet` | | |
| 2.4 | Deactivate the owner while the owner is signed in, then the owner clicks anything | Owner is signed out: "no longer active" | | |
| 2.5 | Activate the owner again | Owner can sign in | | |
| 2.6 | Pets → set a primary vet | Owner's pet card shows "Vet: ..." | | |
| 2.7 | Appointments → No vet yet → assign to a vet who already has a visit at that time | ❌ "already has an appointment at ..." | | |
| 2.8 | Assign to a free vet | Assigned; the vet gets a 🔔 | | |
| 2.9 | Cancel an upcoming appointment with a reason | Cancelled; the owner gets a 🔔 with the reason | | |

## 3. Pet Owner
| # | Do | Expected | ✅/❌ | Notes |
|---|---|---|---|---|
| 3.1 | Add a pet with a photo | Pet card with photo, age and sex | | |
| 3.2 | Edit the pet; archive another pet | Changes saved; archived pet disappears | | |
| 3.3 | Book an appointment (no vet chosen) | Pending; shows in Appointments and on Home | | |
| 3.3b | Book with the reason "ate chocolate and has a seizure" | Red 🚨 Emergency card: go to the clinic now; the vet sees the request first with a red border | | |
| 3.4 | Book a time in the past / outside 8 AM–5 PM | ❌ Refused | | |
| 3.5 | Add a medicine with 2 dose times starting today | Doses appear in Meds and on Home | | |
| 3.6 | Mark one dose given, skip another | Green "given", grey "skipped"; adherence % changes | | |
| 3.7 | Leave a dose past its time, then open Home | Marked **Missed**; red alert and a 🔔 | | |
| 3.8 | Log the journal 2 days → **Create summary** | Summary with a concern level | | |
| 3.9 | AI Chat: "What is otitis externa?" | Plain-language answer (or "Offline glossary answer") | | |
| 3.10 | Open the Timeline, try each filter | Events grouped by month; filters work | | |

## 4. Veterinarian
| # | Do | Expected | ✅/❌ | Notes |
|---|---|---|---|---|
| 4.1 | Home | Today's schedule and the owner's new request | | |
| 4.2 | **Confirm & assign to me** | Confirmed, "You"; owner gets a 🔔 | | |
| 4.3 | Confirm a request at the same time as another of your visits | ❌ "You already have another appointment" | | |
| 4.4 | **Decline** with a reason | Cancelled; owner's 🔔 shows the reason | | |
| 4.5 | Patients → search the owner's name | The owner's pets are listed | | |
| 4.6 | Patient page | Allergies, owner contact, journal summary, last 7 days, medicines with adherence % | | |
| 4.7 | From a confirmed appointment: **📝 Add record** with vitals, diagnosis, follow-up, private notes | Saved; the visit becomes **Completed**; the pet's weight is updated | | |
| 4.8 | Add record with temperature 60 / visit date in the future | ❌ Error; typed values kept | | |
| 4.9 | **Record vaccine** with **+1 yr** | Next due date filled one year after the date given | | |
| 4.10 | **Prescribe** with 2 dose times | Saved | | |
| 4.11 | Journals → **Mark reviewed** | Shows ✓ Reviewed and moves down | | |
| 4.12 | **No-show** on a confirmed visit whose time has passed | Status "No show" | | |

## 5. Owner sees the vet's work
| # | Do | Expected | ✅/❌ | Notes |
|---|---|---|---|---|
| 5.1 | 🔔 Notifications | "New visit record", "Vaccine recorded", "New prescription" | | |
| 5.2 | Timeline | Consultation, Vaccination, Medication Started, Follow-up Due | | |
| 5.3 | Timeline text | The vet's **private notes are not shown** | | |
| 5.4 | Meds | The prescribed medicine with its dose times | | |
| 5.5 | Timeline → **✨ View & explain this visit** → **Explain in simple words** | Plain-language explanation with the vet words explained; **no private notes** | | |
| 5.6 | Open `/timeline/records/<id of another owner's record>` | Sent back to the Timeline: "Record not found." | | |

## 6. Phone and browser
| # | Do | Expected | ✅/❌ | Notes |
|---|---|---|---|---|
| 6.1 | Open on a phone (or DevTools → phone size) | No sideways scrolling; bottom menu fits | | |
| 6.2 | Landing page (`/`) on phone and desktop | Clinic open/closed bar, steps, services, FAQ | | |
| 6.3 | Leave a form open for 2+ hours, then submit | Friendly "expired" message; reload and submit works | | |

## 7. Backup and restore
| # | Do | Expected | ✅/❌ | Notes |
|---|---|---|---|---|
| 7.1 | `php spark db:backup` | File saved in `writable/backups/` | | |
| 7.2 | Import it into a new empty database and point `.env` to it | Sign-in, Timeline and Meds work with the same data | | |
| 7.3 | Before zipping / uploading the project | `ANTHROPIC_API_KEY` removed from `.env` | | |

---

## Known limits (say these during the defense)
- **E-mail:** the system does not send e-mails yet. The password-reset link is shown on screen only in development mode.
- **Reminders without a scheduler:** due and missed doses are checked when the owner opens the app (no background job / cron).
- **AI:** needs internet and an API key; without them the chatbot and journal summary use the built-in offline rules.
- **One clinic:** opening hours and the clinic name are set in `app/Config/Clinic.php`.
