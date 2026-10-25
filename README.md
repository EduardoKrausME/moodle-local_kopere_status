# Kopere Status (local_kopere_status)

A **Status** page for your Moodle: shows whether the platform is online, **for how long**, **for how many consecutive days**, and provides a **public status page** to share with your team and users.

> **Note on CRON**
> For reliable measurements, configure Moodle’s CRON to run at an interval **less than or equal to** the **check interval** set in the plugin.
> **Example:** if the check runs every **5 minutes** and CRON runs every **10 minutes**, about **50% of checks will fail** (missed windows). Ideally, run CRON **every 1 minute**.

## Features

* Moodle **availability monitoring**.
* Continuous **uptime counter** and **consecutive days online**.
* **History** of checks and downtime periods.
* **Public status page** (open read access) to communicate uptime transparently.

**crontab example (Linux only):**

```cron
* * * * * /usr/bin/php /var/www/html/moodle/admin/cli/cron.php >/dev/null 2>&1
```

## Usage

Visit [https://MOODLE.com/local/kopere_status](https://MOODLE.com/local/kopere_status)

* **Public page:** open-read URL (no login) to share with your community.

> **Tip:** Include the public status link in the site footer or in the support area.

## Best practices & tips

* **CRON must not be less frequent** than the check interval: keep CRON **≤** the check (e.g., check every 5 min → CRON 1–5 min).
* Synchronize **time zone** and **server time** to avoid window discrepancies.
* In **proxy/CDN** environments, ensure the check hits the correct endpoint (apply a WAF *allowlist* if needed).

## Credits

Plugin by **Eduardo Kraus**.
