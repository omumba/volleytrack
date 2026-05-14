# VolleyTrack Live Streaming Guide

## Overview

VolleyTrack supports live streaming for every match with a built-in scoreboard overlay that updates automatically every 5 seconds. Viewers watching the stream see the live set score and current-set score change in real time without refreshing the page — powered by the `/api/match_score.php` endpoint that the player polls continuously.

---

## How the Score Overlay Works

When a camera is **Live** and the match status is **Live**, the stream player shows two overlays:

- **Top overlay** — team short names and current sets won (e.g. `BLS 1 – 0 LLT`)
- **Bottom overlay** — current set number and point score (e.g. `Set 2 · 18–14`)

These values are pulled from the database and refresh every 5 seconds automatically. Every time a scorer updates the score in **Admin → Score Entry**, all viewers see the change within 5 seconds — no manual refresh needed.

The dedicated Scoreboard page (`/modules/scoreboard/index.php`) does the same and also shows the full action log (aces, kills, blocks, etc.) updating live.

---

## Step-by-Step: Going Live

### Step 1 — Create the Match

Go to **Admin → Add Match** and schedule the match. Set status to `Scheduled`. The match will appear in the stream management list.

---

### Step 2 — Add Cameras to the Match

Go to **Admin → Live Streams**, click the match in the left panel, then click **Add Camera**.

Fill in the form:

| Field | What to enter |
|---|---|
| **Camera Label** | Descriptive name viewers will see — e.g. `Main Court`, `Attack Camera` |
| **Provider** | Choose your stream source type (see providers below) |
| **Stream Key** | Leave blank to auto-generate, or type your own |
| **HLS Playback URL** | For RTMP/HLS — the URL your media server exposes |
| **Embed URL** | For YouTube / Facebook / Custom — the embed iframe URL |
| **Primary camera** | Tick for the camera that loads by default for viewers |

You can add as many cameras as needed. Viewers switch between them using tabs on the stream page.

---

### Step 3 — Configure Your Stream Source

#### Option A: RTMP / HLS (Self-hosted — recommended for full control)

You need a media server running alongside WampServer. The simplest options are:

**MediaMTX** (easiest on Windows):
1. Download MediaMTX from [https://github.com/bluenviron/mediamtx](https://github.com/bluenviron/mediamtx)
2. Run `mediamtx.exe` — it listens on RTMP port `1935` and serves HLS on port `8888` by default
3. In OBS or your encoder, set:
   - Server: `rtmp://localhost/live`
   - Stream Key: the key shown in VolleyTrack (e.g. `vt-m7-main`)
4. Your HLS Playback URL becomes: `http://YOUR_SERVER_IP:8888/live/vt-m7-main/index.m3u8`
5. Paste that URL into the **HLS Playback URL** field in VolleyTrack

**nginx-rtmp** (Linux / advanced):
- RTMP ingest: `rtmp://YOUR_SERVER/live/STREAM_KEY`
- HLS output: `http://YOUR_SERVER/hls/STREAM_KEY.m3u8`

The stream player uses **HLS.js** automatically in Chrome/Firefox. Safari and iOS play HLS natively.

---

#### Option B: YouTube Live (easiest — no server needed)

1. Go to [YouTube Studio](https://studio.youtube.com) → **Go Live**
2. Set visibility to **Public** or **Unlisted**
3. In OBS, use the YouTube stream key
4. Once the stream is active, click **Share** → **Embed** and copy the embed URL  
   It looks like: `https://www.youtube.com/embed/LIVE_VIDEO_ID`
5. In VolleyTrack: set Provider to **YouTube**, paste the embed URL into **Embed URL**

---

#### Option C: Facebook Live

1. Go to your Facebook Page → **Live Video**
2. Copy the embed code Facebook provides
3. Extract the `src` URL from the iframe — it looks like:  
   `https://www.facebook.com/plugins/video.php?href=...`
4. In VolleyTrack: set Provider to **Facebook**, paste into **Embed URL**

---

#### Option D: Custom Embed

Any service that provides an iframe embed URL works — Zoom, Vimeo, Restream, etc.  
Set Provider to **Custom** and paste the embed URL.

---

### Step 4 — Start Scoring

Open **Admin → Score Entry** and select the match. The moment you click **Start Match**, the match status flips to `Live`. This:

- Activates the score overlay on the stream page
- Makes the match appear in the live stream selector for viewers
- Starts the 5-second score polling on all open scoreboard and stream pages

Every point you record updates `home_score_current_set` and `away_score_current_set` in the database. When a set ends and you close it, `home_sets_won` / `away_sets_won` increment and the set score is archived to `set_scores` JSON.

All of this is reflected on viewers' screens within 5 seconds.

---

### Step 5 — Set Camera Status to Live

Back in **Admin → Live Streams**, for each active camera click the **Live** button. This:

- Sets the camera `status = 'Live'`
- Auto-sets the match to `Live` if it was still `Scheduled`
- Makes the video player render on the stream page (it only shows for Live cameras)

Set cameras that aren't streaming to **Offline** so viewers don't see a broken player.

---

### Step 6 — Share the Stream Link

The public stream URL is:

```
http://localhost/volleyball/modules/streaming/index.php?match=MATCH_ID
```

Share this with fans. If multiple cameras are configured, they can switch using the camera tabs at the top of the player.

The dedicated scoreboard link (score-only, no video) is:

```
http://localhost/volleyball/modules/scoreboard/index.php?match=MATCH_ID
```

This is useful to display on a second screen or embed in a broadcast graphics system.

---

### Step 7 — End the Stream

When the match finishes:

1. In **Admin → Score Entry**, click **End Match** — status becomes `Completed`
2. In **Admin → Live Streams**, set each camera to **Ended**
3. The stream page shows "Stream has ended" to viewers, with a **Watch Replay** button if a recording is available
4. The scoreboard shows the final score permanently

---

### Step 8 — Add a Recording (optional)

After the match ends you can attach a VOD (video on demand) link so fans can rewatch the game at any time.

1. Go to **Admin → Live Streams**, select the completed match
2. Click the **Edit** (pencil) button on any camera
3. Paste the recording URL into the **Recording URL** field
4. Click **Save**

The match will now appear on the public **Replays** tab of the stream page.

**Supported recording URL formats:**

| Format | Example |
|---|---|
| YouTube VOD embed | `https://www.youtube.com/embed/VIDEO_ID` |
| Facebook video embed | `https://www.facebook.com/plugins/video.php?href=...` |
| Direct MP4 file | `https://yourserver.com/recordings/match7.mp4` |
| HLS recording | `https://yourserver.com/recordings/match7.m3u8` |
| Custom embed (Vimeo, etc.) | Any iframe `src` URL |

You can add a different recording URL to each camera — for example, one angle on YouTube and a full match file as an MP4.

---

## What Viewers See

The stream page has two tabs at the top: **Live & Upcoming** and **Replays**.

### Live & Upcoming tab

```
┌─────────────────────────────────────────────────────┐
│ [BLS 1 – 0 LLT]                          ● LIVE    │  ← top overlay (updates every 5s)
│                                                      │
│                                                      │
│              VIDEO PLAYER                            │
│                                                      │
│                                                      │
│  Set 2 · 18–14                                       │  ← bottom overlay (updates every 5s)
└─────────────────────────────────────────────────────┘
│ Main Court  │ Attack Camera  │ Defense Camera  │     │  ← camera tabs
└─────────────────────────────────────────────────────┘
│                    LIVE CHAT                         │
│  ChikondiM  14:12   Great match so far!              │
│  TakondwaV  14:14   Banda is on fire!                │
│  ──────────────────────────────────────────────────  │
│  [Your name]  [Message...]                   [Send]  │
└─────────────────────────────────────────────────────┘
```

- Score overlay updates **every 5 seconds** from the database
- Chat updates **every 3 seconds** (new messages appear without refresh)
- Viewers do not need an account to watch or chat (guest name supported)

### Replays tab

```
┌─────────────────────────────────────────────────────┐
│ [BLS 2 – 1 LLT]                        ▶ REPLAY    │  ← final score overlay
│                                                      │
│                                                      │
│              RECORDED VIDEO                          │
│                                                      │
│                                                      │
└─────────────────────────────────────────────────────┘
│ Main Court  │ Attack Camera  │                       │  ← camera tabs (per recording)
└─────────────────────────────────────────────────────┘
│                   MATCH CHAT                         │
│  (original live chat messages from the match)        │
└─────────────────────────────────────────────────────┘
```

- Only completed matches with at least one recording URL appear here
- The final set score is shown in the overlay instead of a live score
- The original live chat from the match is displayed (read-only — no new polling)
- Each camera angle can have its own recording URL; viewers switch between them with tabs

---

## Camera Switching

When more than one camera is configured for a match, tabs appear above the player. Clicking a tab reloads the page with `?cam=CAMERA_ID` and shows that camera's feed and its own separate chat history.

Mark one camera as **Primary** — this is the one that loads by default when a viewer opens the stream page without specifying a camera.

---

## Scoreboard-Only Mode (for broadcast graphics)

The scoreboard page at `/modules/scoreboard/index.php?match=ID` shows the score in a large format with no video. It polls the same API every 5 seconds.

You can open this in a browser source inside OBS and overlay it on top of your video feed as a graphic layer, or display it on a separate screen at the venue.

---

## Troubleshooting

| Problem | Likely cause | Fix |
|---|---|---|
| Video player not showing | Camera status is not `Live` | Set status to **Live** in Admin → Streams |
| Score overlay not appearing | Match status is not `Live` | Click **Start Match** in Score Entry |
| Score not updating | Scorer not recording actions | Check Admin → Score Entry is open and being used |
| HLS stream not playing | Wrong HLS URL or media server not running | Verify the `.m3u8` URL loads in a browser |
| YouTube embed blocked | Autoplay policy | Viewer needs to click play once; muted autoplay may work |
| Camera tabs not showing | Only one camera configured | Add more cameras in Admin → Streams |
| Match not appearing in Replays | No recording URL set | Edit the camera in Admin → Streams and add a Recording URL |
| Replay video not playing | Wrong URL format | Check the URL opens directly in a browser; for YouTube use the `/embed/` form |

---

## Summary Checklist

- [ ] Match created and scheduled
- [ ] Cameras added with correct provider and URL
- [ ] One camera marked as Primary
- [ ] Encoder (OBS / phone) pointed at RTMP server, or YouTube/Facebook live started
- [ ] Score Entry open — scorer ready at the venue
- [ ] Match started in Score Entry (status → Live)
- [ ] All streaming cameras set to **Live** in Admin → Streams
- [ ] Stream link shared with viewers
- [ ] After match: cameras set to **Ended**, match ended in Score Entry
- [ ] (Optional) Recording URL added to each camera for replay access
