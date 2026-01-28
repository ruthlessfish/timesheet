# Development Roadmap

This is the official development roadmap for **Timeshit**, _the Laravel Time Tracking Application_. Here you'll find planned features, upcoming improvements, and key milestones. This document is regularly updated to reflect the project's direction and priorities. Whether you're a contributor or a user, this roadmap provides insight into what's coming next and how you can get involved.

---

## 🎯 Current Status (January 2026)

**Production Ready v1.0** ✅
- ✅ Service layer architecture (4 services, 40 methods)
- ✅ Complete REST API with Sanctum authentication
- ✅ 118 tests, 428 assertions, 100% passing
- ✅ Rate limiting (60/min general, 5/min auth)
- ✅ Dashboard analytics with Chart.js
- ✅ Invoice generation with PDF export
- ✅ 4-level hourly rate cascade
- ✅ Active timer management

**Phase 5 Complete** ✅ (January 28, 2026)
- ✅ Dark mode toggle with system preference detection
- ✅ Calendar view with FullCalendar integration
- ✅ Bulk operations (delete, edit) for time entries

**Tech Stack**:
- Laravel 12 with Breeze authentication
- Tailwind CSS + Alpine.js
- SQLite (production-ready for PostgreSQL/MySQL)
- Chart.js for visualizations
- Dompdf for PDF generation

---

## 🚀 Future Development Ideas

### Priority 1: Quick Wins (Low Effort, High Impact)

#### 📊 Enhanced Analytics & Exports
- **Custom Date Range Reports** - Allow filtering by arbitrary date ranges
- **CSV Export** - Download time entries, projects, clients as spreadsheet
- **Excel Export** - XLSX format with formulas and formatting
- **Monthly/Quarterly PDF Reports** - Automated summary reports
- **Profitability Dashboard** - Show profit margins per project/client
- **Comparison Reports** - Month-over-month, year-over-year trends

**Complexity**: Low-Medium | **Impact**: High | **Timeline**: 2-3 weeks

#### 🎨 UX Improvements
- **Dark Mode** - Toggle light/dark theme
- **Keyboard Shortcuts** - `Ctrl+T` to start timer, `Ctrl+S` to stop, etc.
- **Timer Notifications** - Desktop notifications when timer reaches thresholds
- **Calendar View** - Month/week calendar showing time entries
- **Time Entry Templates** - Save frequently used descriptions/settings
- **Bulk Operations** - Select multiple entries to delete/edit/invoice

**Complexity**: Low-Medium | **Impact**: High | **Timeline**: 2-4 weeks

#### 📧 Invoice Automation
- **Email Invoices** - Send invoices directly from app
- **Recurring Invoices** - Auto-generate monthly/weekly invoices
- **Invoice Templates** - Customize invoice design/branding
- **Payment Tracking** - Mark invoices as partially paid
- **Payment Reminders** - Automated email reminders for overdue invoices
- **Invoice Preview** - See invoice before creating (current: create then view)

**Complexity**: Medium | **Impact**: High | **Timeline**: 3-5 weeks

---

### Priority 2: Major Features (Medium Effort, High Impact)

#### 👥 Team Collaboration
- **Multi-User Teams** - Invite team members to workspace
- **Role-Based Permissions** - Admin, Manager, Employee roles
- **Team Time Sheets** - View all team members' time
- **Approval Workflows** - Managers approve time entries before invoicing
- **Activity Logs** - Audit trail of all changes
- **Comments on Entries** - Discuss specific time entries
- **@Mentions** - Notify team members in comments

**Complexity**: High | **Impact**: Very High | **Timeline**: 8-12 weeks
**Requirements**: Multi-tenancy architecture, permission system

#### 💰 Payment Processing
- **Stripe Integration** - Accept online payments
- **Payment Portal** - Client-facing portal to view/pay invoices
- **Partial Payments** - Track multiple payments per invoice
- **Credit Notes** - Issue refunds and credits
- **Multi-Currency** - Support multiple currencies with conversion
- **Payment History** - Track all payments across clients

**Complexity**: High | **Impact**: Very High | **Timeline**: 6-8 weeks
**Requirements**: Stripe account, SSL certificate

#### 📱 Mobile Applications
- **Progressive Web App (PWA)** - Install on mobile home screen
- **Offline Support** - Track time without internet, sync later
- **Push Notifications** - Mobile notifications for timer events
- **Quick Timer Widget** - Home screen widget for instant timer start
- **GPS Location Tracking** - For field workers (optional)

**Complexity**: High | **Impact**: Very High | **Timeline**: 10-16 weeks
**Tech**: Service workers, IndexedDB, Push API

---

### Priority 3: Integrations (Medium Effort, Medium-High Impact)

#### 🔗 Third-Party Integrations
- **Slack Bot** - Start/stop timer via Slack commands
- **Google Calendar Sync** - Two-way sync with calendar events
- **GitHub/Jira** - Link time entries to issues/tickets
- **QuickBooks/Xero** - Export invoices to accounting software
- **Zapier Webhooks** - Trigger workflows on events
- **OAuth2 Provider** - Allow third-party apps to integrate
- **Import Tools** - Import from Toggl, Harvest, Clockify

**Complexity**: Medium-High | **Impact**: Medium-High | **Timeline**: 4-6 weeks per integration

#### 📧 Email & Calendar
- **SMTP Configuration** - Send emails via app
- **Email Templates** - Customizable email designs
- **Calendar Export** - .ics files for time entries
- **Outlook Integration** - Two-way sync with Microsoft 365
- **Automated Reports** - Weekly/monthly email reports

**Complexity**: Medium | **Impact**: Medium | **Timeline**: 3-4 weeks

---

### Priority 4: Advanced Features (High Effort, Medium Impact)

#### 🤖 Automation & AI
- **Smart Time Suggestions** - Suggest project based on patterns
- **Auto-Categorization** - ML to classify time entries
- **Anomaly Detection** - Flag unusual time entries for review
- **Predictive Analytics** - Forecast project completion times
- **Smart Reminders** - Remind users to log time based on habits
- **Time Blocking** - Suggest optimal work schedules

**Complexity**: Very High | **Impact**: Medium | **Timeline**: 12-20 weeks
**Requirements**: ML models, training data, cloud processing

#### 📦 Project Management Features
- **Project Budgets** - Set time/money budgets, track against them
- **Milestones** - Track project phases and deadlines
- **Task Management** - Break projects into tasks
- **Gantt Charts** - Visual project timelines
- **File Attachments** - Upload files to projects
- **Project Templates** - Reusable project structures
- **Capacity Planning** - Team workload balancing

**Complexity**: Very High | **Impact**: Medium-High | **Timeline**: 16-24 weeks
**Note**: Might overlap with dedicated PM tools (Asana, Monday.com)

#### 🔐 Enterprise Features
- **Two-Factor Authentication (2FA)** - TOTP, SMS, email codes
- **Single Sign-On (SSO)** - SAML, OAuth integration
- **GDPR Compliance** - Data export, right to deletion
- **IP Whitelisting** - Restrict access by IP
- **Advanced Audit Logs** - Detailed activity tracking
- **Data Encryption at Rest** - Encrypt sensitive database fields
- **Multi-Tenancy** - Separate databases per organization

**Complexity**: Very High | **Impact**: Medium (for enterprise) | **Timeline**: 12-16 weeks

---

### Priority 5: Developer Tools (Medium Effort, Low-Medium Impact)

#### 🛠️ Developer Experience
- **GraphQL API** - Alternative to REST API
- **WebSocket Support** - Real-time timer updates
- **CLI Tool** - Command-line time tracking
- **VS Code Extension** - Track time from code editor
- **Browser Extension** - Quick timer from any website
- **OpenAPI/Swagger Docs** - Interactive API documentation
- **SDK Libraries** - PHP, JavaScript, Python client libraries
- **Webhook System** - Subscribe to app events

**Complexity**: Medium-High | **Impact**: Low-Medium | **Timeline**: 4-8 weeks per tool

#### 📚 API Enhancements
- **API v2** - Improved endpoint structure
- **Rate Limit Customization** - Per-user rate limits
- **API Analytics** - Track API usage per token
- **Batch Operations** - Create multiple resources in one call
- **Field Selection** - GraphQL-like field filtering
- **Pagination Improvements** - Cursor-based pagination

**Complexity**: Medium | **Impact**: Medium | **Timeline**: 4-6 weeks

---

### Priority 6: Nice-to-Haves (Low Priority)

#### 🎨 Customization
- **Custom Fields** - User-defined fields for all resources
- **White-Labeling** - Full branding customization
- **Custom Report Builder** - Drag-and-drop report designer
- **Workflow Automation** - If-this-then-that rules
- **Theme Marketplace** - Community-contributed themes
- **Plugin System** - Extensible architecture

**Complexity**: Very High | **Impact**: Low-Medium | **Timeline**: 20+ weeks

#### 🌍 Internationalization
- **Multi-Language** - i18n support (Spanish, French, German, etc.)
- **Timezone Support** - Per-user timezone settings
- **Regional Formats** - Date/time/currency formats
- **RTL Support** - Right-to-left languages (Arabic, Hebrew)

**Complexity**: Medium-High | **Impact**: Low-Medium | **Timeline**: 6-8 weeks

---

## 🗺️ Suggested Roadmap Timeline

### Phase 5: Polish & UX (Q1 2026) - 6-8 weeks ✅ COMPLETED
**Focus**: Improve existing features, make app delightful
- ✅ Dark mode toggle
- ✅ Calendar view of entries
- ✅ Bulk operations

### Phase 6: Analytics & Reporting (Q2 2026) - 8-10 weeks
**Focus**: Give users insights into their time
- [ ] Custom date range reports
- [ ] CSV/Excel export
- [ ] PDF summary reports
- [ ] Profitability analysis
- [ ] Comparison reports (MoM, YoY)
- [ ] Advanced dashboard widgets

### Phase 7: Invoice Automation (Q2 2026) - 6-8 weeks
**Focus**: Streamline billing workflow
- [ ] Email invoice sending
- [ ] Recurring invoices
- [ ] Invoice templates/branding
- [ ] Payment tracking
- [ ] Automated reminders
- [ ] Client payment portal (basic)

### Phase 8: Team Features (Q3 2026) - 12-16 weeks
**Focus**: Support multi-user teams
- [ ] Team workspaces
- [ ] Role-based permissions
- [ ] Team time sheets
- [ ] Approval workflows
- [ ] Activity logs
- [ ] Comments & mentions

### Phase 9: Payment Processing (Q3-Q4 2026) - 8-10 weeks
**Focus**: Enable online payments
- [ ] Stripe integration
- [ ] Payment portal
- [ ] Multi-currency support
- [ ] Payment history
- [ ] Credit notes/refunds

### Phase 10: Mobile & PWA (Q4 2026) - 12-16 weeks
**Focus**: Mobile-first experience
- [ ] Progressive Web App
- [ ] Offline support
- [ ] Push notifications
- [ ] Mobile UI optimization
- [ ] Quick timer widget

### Phase 11: Integrations (2027+) - Ongoing
**Focus**: Connect with other tools
- [ ] Slack bot
- [ ] Google Calendar sync
- [ ] GitHub/Jira integration
- [ ] Accounting software exports
- [ ] Zapier webhooks

---

## 💡 How to Contribute Ideas

Have a feature idea? Here's how to suggest it:

1. **Check Existing Issues** - Search GitHub issues for duplicates
2. **Open a Discussion** - Use GitHub Discussions for brainstorming
3. **Create Feature Request** - Use issue template for formal requests
4. **Vote on Features** - 👍 on issues to show interest
5. **Submit PR** - Implement small features yourself!

---

## 📊 Decision Criteria

When prioritizing features, we consider:

1. **User Impact** - How many users benefit?
2. **Effort Required** - Developer hours needed
3. **Strategic Value** - Does it differentiate us?
4. **Technical Debt** - Does it improve architecture?
5. **Community Demand** - How many requests?
6. **Revenue Potential** - For commercial version

---

## 🎯 Current Focus (Next 3 Months)

Based on user feedback and strategic direction:

1. ✅ **Dark Mode** - Most requested feature (COMPLETE)
2. **CSV/Excel Export** - Critical for accountants
3. **Email Invoices** - Essential for professional use
4. ✅ **Calendar View** - Better time visualization (COMPLETE)
5. ✅ **Bulk Operations** - Power user productivity (COMPLETE)

**Vote on priorities**: [GitHub Discussions](https://github.com/ruthlessfish/timeshit/discussions)

---

*Last updated: January 28, 2026*
*Next review: February 28, 2026*
