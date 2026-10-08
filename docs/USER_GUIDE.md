# PawRecord User Guide

PawRecord has three kinds of accounts. Each one sees its own home page and bottom menu after signing in.

| Role | Who | How the account is made | Home page |
|---|---|---|---|
| **Pet Owner** | Clients | **Sign up** on the login page | `/owner` |
| **Veterinarian** | Clinic vets | Clinic Staff → **+ Add vet** | `/vet` |
| **Clinic Staff (Admin)** | Front desk / manager | The first one on `/setup`; others with **+ Add clinic staff** | `/admin` |

**Forgot password:** the login page → **Forgot password?** → enter the email. The reset link works for 1 hour.
The link is e-mailed when the clinic has set up e-mail in `.env` (README, step 7). Without it, in development mode the link is shown on the screen.

---

## 🐾 Pet Owner

**Bottom menu:** Home · AI Chat · Timeline · Meds · Journal · 🔔 (top bar)

### Home
- Your pet's card: age, sex, primary vet, next appointment, vaccine status. Tap 📷 to change the photo, ✏️ to edit the pet.
- **Today's alerts:** doses to give, doses missed, and a reminder when today's journal is not logged yet.
- With more than one pet, tap the pet's name at the top to switch. **+ Add pet** adds another.

### Appointments
1. **Book an appointment** → choose the pet, the type of visit, the date and a free time (8:00 AM – 5:00 PM, 30 minutes).
   You may pick a vet or leave it to the clinic.
   Write the symptoms in **Reason**: the AI checks how urgent it is and shows advice right away
   (🚨 Emergency means do not wait for the appointment: go to the clinic now).
2. The clinic staff (and the vet you chose) get a 🔔 right away; urgent requests say "🚨 Emergency" or "⚠️ Urgent".
   The request is **Pending** until a vet confirms it. You get a 🔔 when it is confirmed or declined (with the reason).
3. You can **cancel** an upcoming appointment.

### Health Timeline
Every consultation, vaccine, medicine and appointment of the pet, newest first, with filters
(Visits · Vaccines · Medications · Appointments). Records written by the vet appear here automatically.
Tap **✨ View & explain this visit** under a visit to see the whole record, then **✨ Explain in simple words**:
the AI rewrites it in plain language and explains the vet words (offline, a built-in explainer is used).

### Meds (Medication Adherence Tracker)
- Medicines prescribed by the vet appear here by themselves. You can also add one from a paper prescription (**+ Add**).
- Each dose time shows **Mark given** or **Skip**. A dose not marked by its time becomes **Missed** and you get a 🔔.
- The **adherence %** shows how many doses were given; your vet sees it too.
- **Stop this medication** ends a medicine early.

### Journal (Symptom & Behavior Journal)
- Once a day: appetite, activity, mood, sleep, water, stool, vomiting, weight, symptoms. You can fix the last 7 days.
- After 2 or more days, **✨ Create summary** makes a short summary of the last 14 days with a concern level.
  Your vet reads it before the visit.

### AI Chat
Ask what a vet word means ("What is otitis externa?"). Answers are general information, **not a diagnosis**.
Without internet / an AI key, answers come from the built-in glossary and say "(Offline glossary answer...)".

---

## 🩺 Veterinarian

**Bottom menu:** Home · Patients · Appointments · Journals · 🔔

### Home
Today's schedule, new requests, and numbers (Today · Requests · Upcoming · Patients).

### Appointments
Tabs: **Today · Requests · Upcoming · Past**. Buttons depend on the status:

| Status | Buttons |
|---|---|
| Pending (no vet yet) | **Confirm & assign to me** — the visit becomes yours (refused if you already have a visit at that time) |
| Pending (yours) | **Confirm** |
| Pending / Confirmed (future) | **Decline** — type a reason; the owner is notified |
| Confirmed (yours) | **📝 Add record**, **Mark completed**; after the time has passed, **No-show** |

Tap the pet's name to open its patient page.

### Patients
Search by pet or owner name. The patient page shows:
- pet details, **allergies** (orange badge), the owner's phone and e-mail
- the owner's latest **journal summary** (with **Mark reviewed**) and the last 7 journal days
- **current medicines** with their adherence % (red under 80%)
- **medical records** (tap one to open it) and **vaccinations** (red when overdue)

Buttons on the patient page:
- **📝 Add record** — visit type and date, complaint, vitals (weight, temperature, heart rate, breathing), findings, diagnosis,
  treatment, follow-up date, and **private notes** (vets only; owners never see them).
  Opened from an appointment, saving it also marks that visit **Completed**. The weight updates the pet's profile.
- **💉 Record vaccine** — name (suggestions), dose no., date given, next due date (quick buttons **+2 wks / +3 wks / +1 yr**).
- **💊 Prescribe** — medicine, dosage, form, purpose, dose times, start/end date. The owner gets dose reminders in Meds.

The owner gets a 🔔 for every record, vaccine and prescription.

### Journals
All journal summaries from owners, **not yet reviewed first**. **Mark reviewed** when you have read one.

---

## 🗂️ Clinic Staff (Admin)

**Bottom menu:** Home · Users · Pets · Appointments · 🔔

**🔔 Notifications:** every new request from an owner (🚨 / ⚠️ in the title when it is urgent) and every
cancellation by an owner. Tap one to open the right Appointments tab.

### Home
Numbers (Pet owners · Vets · Pets · Today), **requests with no vet** (assign them right there), and every visit today.

### Users
- Tabs **All · Pet Owners · Vets · Staff** with counts, and a search by name or e-mail.
- **+ Add staff** → choose **Veterinarian** (license no. and specialization) or **Clinic Staff**, and give a temporary password
  (at least 8 characters). Tell the person to change it.
- **Deactivate** stops an account from signing in (it is signed out on its next click). **Activate** turns it back on.
  Accounts are never deleted, so old records keep the vet's name. You cannot deactivate yourself.

### Pets
Every pet with its owner and last visit. Choose a **primary vet** and tap **Save vet**; the owner sees "Vet: ..." on the pet card.

### Appointments
Tabs **Today · No vet yet · Upcoming · Past**. For upcoming visits:
- **Assign / Change** the vet (refused if that vet already has a visit at the same time). The vet gets a 🔔.
- **Cancel** with a reason; the owner gets a 🔔.
