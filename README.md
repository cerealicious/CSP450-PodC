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

## 🎓 Course Context & Expectations

### The Capstone Challenge
CSP450 is not just another standalone course; it is the **culmination of our entire program**. As stated in the syllabus, this project is designed to apply knowledge acquired from **CSN305 (Networking), OPS345 (Linux Admin), MST300 (Windows/Server), and SEC320 (Security)**. 

If you feel lost or overwhelmed, **you are not alone**. This is a common reaction because:
1.  **Integration Complexity:** We are no longer dealing with isolated tasks. We are building an **interconnected multi-server environment** where a mistake in one layer (e.g., VLANs) breaks another (e.g., Database connectivity).
2.  **Simulation vs. Reality:** Many of us learned networking basics using **Cisco Packet Tracer**. While useful for theory, Packet Tracer hides the complexities of real hardware, Linux command-line nuances, and physical cabling issues. Transitioning to **real switches** and **on-premises server administration** requires a shift in mindset from "clicking buttons" to "understanding protocols."

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
| **[Stage Three](./StageThree/)** | 🚧**In-Progress** | Enterprise MariaDB integration, schema deployment, and PHP frontend connectivity. |

## 📖 Documentation
For detailed, step-by-step instructions on how the network layer was planned and executed, please refer to the specific stage documentation folder:
* **[Stage Two README](./StageTwo/README.md)**: Contains the full matrix, infrastructure prerequisite packages, and physical topology blueprints.

## 📁 Additional Resources
* **[Templates](./StageTwo/Templates/)**: Configuration files used during the project deployment pipeline.
* ~~**My Personal Screenshots**: Visual metric captures and packet verification logs used for laboratory report compliance.~~

---
*Last Updated: July 01, 2026*
