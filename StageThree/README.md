# Stage Three: Enterprise Database Integration & Application Tier

## 🎯 Core Objective
In Stage Two, we successfully established the network infrastructure layer, including OSPF routing, VLAN segmentation, and edge NAT firewalls. 

In Stage Three, the focus shifts to bringing an enterprise database application online across that secured network. The primary task is to transform the Ubuntu Server VM into a secure database host running **MariaDB**, initialize a custom relational data scheme, secure it via internal loop configurations, and connect it to a web-driven PHP frontend utility.

## 🗺️ System Blueprint & Implementation Phases
All implementation steps replace generic examples with our unique corporate profile name: **catalan**.

### Phase 1: DB Infrastructure & Environment Provisioning
- Install core `mariadb-server` engines on the Ubuntu Server VM.
- Configure listener bindings to the local production address (`172.16.57.254`).

### Phase 2: Database Initialization & Schema Deployment
- Construct custom relational tables using raw SQL script blocks based on the provided spreadsheet fields.
- Define explicit record constraints and index boundaries to ensure data integrity.

### Phase 3: Relational Data Population
- Populate tables with real data keys derived from the CSV layout.

### Phase 4: Multi-Tenant Database Access & Security Policies
- Create custom internal users with distinct roles.
- Assign strict administrative privileges using `GRANT PRIVILEGES` to enforce least-privilege access.

### Phase 5: Application Tier Integration (PHP Frontend)
- Deploy and configure the `mariaDBfrontEnd.php` script.
- Establish connectivity between workstations and the database engine over active switch trunk paths.

### Phase 6: Visual Inspection, Verification, & Reporting
- Perform validation checks on the database state and frontend connectivity.
- Capture validation strings and metrics for the final lab sign-off sheet.

---
*Last Updated: June 29, 2026*
