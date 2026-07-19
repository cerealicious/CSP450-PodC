# 🌐 CSP450 Capstone Project: Network & Infrastructure Blueprint

> **Master Documentation Repository**  
> A comprehensive architectural guide and implementation blueprint for the CSP450 Capstone Challenge. This repository systematically documents the progression of network infrastructure, database integration, and application deployment.

---

## 🚨 CRITICAL WARNING FOR CLASSMATES & USERS

> [!WARNING]  
> **DO NOT COPY THE IP ADDRESSES OR UIDs IN THIS REPO DIRECTLY!**  
> These configurations are calculated specifically for **Pod C, UID 231**, and partner **UID 217**.  
> Pasting these configurations without modification will cause:
> - 🛑 Network failures in your environment.
> - 🛑 IP conflicts across the rack room.
> - 🛑 Disruption of your classmates' networks.

### ✅ Before Running Any Script or Configuration:
1. **Replace UIDs**: Substitute `231` and `217` with your specific student UIDs.
2. **Recalculate Subnets**: Update private LAN subnets based on your unique `/26` parameters.
3. **Update MAC Addresses**: Modify all `static-bind` variables to match the specific fingerprints of your virtual machine interface cards.

---

## 🗺️ How to Use This Guide Effectively

This repository serves as a **conceptual blueprint**, not a copy-paste solution. Use it to understand the *logic* behind each implementation step.

| Step | Action |
| :--- | :--- |
| **1️⃣ Open Prep Sheet** | Review `catalan-PrepSheet.txt` in this repository. |
| **2️⃣ Map Variables** | Cross-reference IPs/subnets (e.g., `172.16.57.192/26`) with the Prep Sheet to identify their source (e.g., "Network ID for Subnet #231"). |
| **3️⃣ Substitute Values** | Replace UID-based values with the equivalent fields from *your* completed Prep Sheet. |

💡 **Pro Tip**: If you are confused about where a specific IP address originated, check the Prep Sheet first. Understanding *why* an IP was chosen is more valuable than simply copying it.

---

## 🎓 Course Context & Expectations

### The Capstone Challenge
CSP450 is the culmination of the entire program, designed to synthesize knowledge acquired from:
- 🌐 **CSN305**: Networking
- 🐧 **OPS345**: Linux Administration
- 🪟 **MST300**: Windows/Server
- 🔒 **SEC320**: Security

### Why This Repository Exists
This guide bridges the gap between theory and practice by providing:
- 🏗️ **Architectural Blueprints**: How to plan your network before touching a cable.
- 🛠️ **Step-by-Step Implementation**: Detailed commands and configurations for Linux and network devices.
- 🔍 **Troubleshooting Logic**: How to diagnose issues when real-world deployments diverge from simulations.

---

## 📂 Project Stages

| Stage | Status | Description |
| :--- | :---: | :--- |
| **[Stage Two](./StageTwo/)** | ✅ Completed | Build the network infrastructure layer (OSPF, routing pools, VLAN segmentation, and edge NAT firewalls). |
| **[Stage Three](./StageThree/)** | ✅ Completed | Enterprise MariaDB integration, schema deployment, and PHP frontend connectivity. |

---

## 📖 Documentation & Resources

For detailed, step-by-step instructions, refer to the specific stage documentation folders:

### 📁 Stage Two: Infrastructure Layer
- **[Stage Two README](./StageTwo/README.md)**: Full matrix, infrastructure prerequisite packages, and physical topology blueprints.
- **[Stage Two Templates](./StageTwo/Templates/)**: Switch configs, firewall rules, and network diagrams.

### 📁 Stage Three: Application Layer
- **[Stage Three Templates](./StageThree/Templates/)**: SQL scripts, PHP source code, and server configuration files.

### 📸 Additional Resources
- ~~**Personal Screenshots**: Visual metric captures and packet verification logs used for laboratory report compliance.~~

---

*📅 Last Updated: July 19, 2026*
