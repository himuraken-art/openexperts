# openexperts.tech

Small business IT services website for Open Experts. Static HTML site — no framework, no build step.

## Hosting

- **Server:** web1.junopi.com (Virtualmin)
- **Path:** `/home/openexperts/public_html/`
- **User:** openexperts (uid 1181)
- **DNS:** Managed externally (not Virtualmin). Authoritative NS: ns1/ns2/ns3.junopi.com. Check against ns3 from local network (ns2 not reachable locally).

## Deployment

GitHub repo → auto-deploy to web1 via cron.

1. Push to `master` on `himuraken-art/openexperts`
2. Cron on web1 runs `/usr/local/bin/openexperts-deploy.sh` every 15 minutes
3. Script does `git fetch` + `git reset --hard origin/master` + fixes ownership

To force an immediate deploy:
```bash
ssh root@web1.junopi.com "/usr/local/bin/openexperts-deploy.sh"
```

## Email / DNS

- **SPF:** `v=spf1 ip4:65.87.199.135 ~all`
- **DKIM:** Selector `202601`, key at `/etc/dkim.key`, openexperts.tech added to `/etc/dkim-domains.txt`
- **DMARC:** `v=DMARC1; p=none; rua=mailto:himuraken@gmail.com; fo=1;`
- **Mail sends from:** `noreply@openexperts.tech` (envelope sender via `-f` flag in PHP mail())
- **Lead notifications go to:** himuraken@gmail.com + aib@junopi.com
- **Auto-reply to guide requesters from:** aib@junopi.com

## Form Handling

`submit.php` handles all form submissions:
- Sanitizes input, validates email, rate-limits (1/min per session)
- Sends notification email to both owner addresses
- For guide downloads: sends auto-reply with guide link to the requester
- Logs all leads to `/leads/leads.csv`
- Lead source tracked via hidden `source` field in forms

### Lead Sources
| Source Value | Label |
|---|---|
| (none) | Consultation Request |
| `cost-reduction` | Cost Reduction Inquiry |
| `cybersecurity-guide` | Cybersecurity Guide Download |
| `it-buyers-guide` | IT Buyer's Guide Download |
| `disaster-recovery-template` | DR Template Download |
| `it-provider-scorecard` | IT Provider Scorecard Download |
| `m365-security-checklist` | M365 Security Checklist Download |
| `onboarding-offboarding-checklist` | Onboarding/Offboarding Checklist Download |
| `m365-licensing-guide` | M365 Licensing Guide Download |
| `vmware-exit-playbook` | VMware Exit Playbook Download |

## Automated Blog Posts

A scheduled Claude Code remote agent generates a new blog post every Monday at 10am ET.

- **Trigger ID:** `trig_016t1ouMmtpgDBKGBEhAhfnz`
- **Manage:** https://claude.ai/code/scheduled/trig_016t1ouMmtpgDBKGBEhAhfnz
- **Model:** Claude Sonnet 4.6
- **Topic bank:** `topics.json` — agent picks next unused topic, writes the post, updates `blog.html`, marks topic used, commits and pushes

38 topics loaded (through ~Jan 2027). To add more, append to `topics.json` and push.

## Site Structure

### Main Pages
| File | Purpose |
|---|---|
| `index.html` | Homepage — hero, pain points, differentiators, ransomware, services overview, contact form |
| `services.html` | Detailed services — servers, security, backups, additional services |
| `proxmox-migration.html` | VMware/Hyper-V cost reduction landing page with comparison table |
| `blog.html` | Blog index with post cards |
| `resources.html` | Free Resources hub — all 8 guides organized by category |

### Blog Posts
| File | Topic |
|---|---|
| `blog-outgrown-it-guy.html` | 5 signs you've outgrown your IT guy |
| `blog-vmware-alternatives.html` | VMware alternatives comparison (2026) |
| `blog-microsoft-365-backup.html` | M365 doesn't back up your data |
| `blog-small-business-it-budget.html` | IT budget framework with real numbers |
| `blog-it-guy-on-vacation.html` | Risk of single-person IT dependency (auto-generated) |

### Lead Magnet Funnels (landing page → guide)
| Landing Page | Guide Content | Audience |
|---|---|---|
| `cybersecurity-guide.html` | `guide-cybersecurity-essentials.html` | Everyone |
| `it-buyers-guide.html` | `guide-it-buyers-guide.html` | Trunk slammer refugees |
| `disaster-recovery-template.html` | `guide-disaster-recovery.html` | Everyone |
| `it-provider-scorecard.html` | `guide-it-provider-scorecard.html` | Trunk slammer refugees |
| `m365-security-checklist.html` | `guide-m365-security.html` | M365 shops |
| `m365-licensing-guide.html` | `guide-m365-licensing.html` | M365 shops |
| `onboarding-offboarding-checklist.html` | `guide-onboarding-offboarding.html` | Growing businesses |
| `vmware-exit-playbook.html` | `guide-vmware-exit.html` | Open source curious |

### Other Files
| File | Purpose |
|---|---|
| `style.css` | All site styles |
| `submit.php` | Form handler + auto-reply |
| `topics.json` | Blog topic bank for scheduled agent |
| `email-sequences.html` | Internal reference — 8 email nurture templates (not deployed/linked) |
| `.htaccess` | Apache config |
| `leads/leads.csv` | Lead log (server-side only, not in repo) |

## Navigation

All pages share this nav: **Blog | Free Resources | What We Do | Free Consultation**

## Next Steps / Ideas

- Set up email nurture sequences using templates in `email-sequences.html` (need an email platform — Mailchimp, ConvertKit, etc.)
- Google Business Profile setup
- Google Ads targeting pain-point keywords
- LinkedIn organic content strategy
- Tighten DMARC from `p=none` to `p=quarantine` once mail is confirmed stable
- Potentially replicate this blog automation pipeline for junopi.com
