# 🌐 CSP450

Welcome to the master documentation repository for my CSP450 project. The explicit goal of this repository is to systematically document my progress and serve as an architectural guide for my classmates to understand the implementation mechanics of each lab stage. While this repository is publicly accessible to provide a conceptual blueprint for the entire class, the configuration parameters, network scopes, and scripts are tailored specifically to the deployment topology used by my groupmates and me.

---

## ⚠️ CRITICAL WARNING FOR CLASSMATES / USERS
> **🛑 DO NOT COPY THE IP ADDRESSES OR UIDs IN THIS REPO AS THIS IS MY OWN CALCULATION BASED ON MY UID!**
> 
> This repository is a blueprint based on a specific lab pod configuration (**Pod C**, **UID 231**, and partner **UID 217**). If you paste these configurations into your switches or virtual machines without modifying them, **your network will break, your classmate's network will break, and you will cause IP conflicts across the rack room.**
>
> BEFORE running any script or applying any configuration from this repository, you **MUST** calculate and substitute your own variables:
> * Replace **`231`** and **`217`** with your specific student UIDs.
> * Recalculate your private LAN subnets based on your own unique `/26` parameters.
> * Update all MAC address variables (`static-bind`) with the specific fingerprints of your virtual machine interface cards.

---
## 🗺️ How to Use This Guide Effectively

This repository serves as a **conceptual blueprint**, not a copy-paste solution. The configurations, scripts, and network parameters documented here are calculated exclusively from **my personal Prep Sheet (UID 231)**. 

### 🔑 Using My Prep Sheet as Your Reference Key
Since every student has unique UIDs and subnet calculations, you cannot directly apply my commands. Instead, use my documentation to understand the *logic* behind each step:

1.  **Open My Prep Sheet:** Review `catalan-PrepSheet.txt` in this repository.
2.  **Map the Variables:** When you see an IP address or subnet in my guide (e.g., `172.16.57.192/26`), cross-reference it with my Prep Sheet to see exactly which field it corresponds to (e.g., "Network ID for Subnet #231").
3.  **Substitute Your Own Values:** Replace my UID-based values with the equivalent fields from **your own** completed Prep Sheet.

> **💡 Pro Tip:** If you are confused about where a specific IP address came from in my configuration, **always check my Prep Sheet first.** It contains the exact formula and source for every network parameter used in this project. Understanding *why* I chose that IP is more valuable than simply copying it.
>
> ---
## 🎓 Course Context & Expectations

### The Capstone Challenge
CSP450 is not just another standalone course; it is the **culmination of our entire program**. As stated in the syllabus, this project is designed to apply knowledge acquired from **CSN305 (Networking), OPS345 (Linux Admin), MST300 (Windows/Server), and SEC320 (Security)**. 

### Why This Repository Exists
This guide aims to bridge the gap between theory and practice. It provides:
*   **Architectural Blueprints:** How to plan your network before touching a cable.
*   **Step-by-Step Implementation:** Detailed commands and configurations for Linux and Network devices.
*   **Troubleshooting Logic:** How to diagnose issues when things don't work as they did in simulation.

---

## 📂 Project Stages

| Stage | Status | Description |
| :--- | :--- | :--- |
| **[Stage Two](./StageTwo/)** | ✅**Completed** | Build the network infrastructure layer (OSPF, routing pools, VLAN segmentation, and edge NAT firewalls. |
| **[Stage Three](./StageThree/)** | 🚧**Completed** | Enterprise MariaDB integration, schema deployment, and PHP frontend connectivity. |

## 📖 Documentation
For detailed, step-by-step instructions on how the network layer was planned and executed, please refer to the specific stage documentation folder:
* **[Stage Two README](./StageTwo/README.md)**: Contains the full matrix, infrastructure prerequisite packages, and physical topology blueprints.

## 📁 Additional Resources
Each stage folder contains its own set of resources to help you replicate the setup:
* **[Stage Two Templates](./StageTwo/Templates/)**: Switch configs, firewall rules, and network diagrams for the infrastructure layer.
* **[Stage Three Templates](./StageThree/Templates/)**: SQL scripts, PHP source code, and server configuration files for the application layer.
* ~~**My Personal Screenshots**: Visual metric captures and packet verification logs used for laboratory report compliance.~~

---
*Last Updated: July 01, 2026*
