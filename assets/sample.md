<p align="center">
  <img src="./assets/hero.svg" alt="CSP450 Capstone Project Banner" width="100%">
</p>

<p align="center">
  <img src="https://readme-typing-svg.demolab.com?font=Fira+Code&weight=600&size=22&pause=1000&color=38BDF8&center=true&vCenter=true&width=600&lines=OSPF+Configuration;VLAN+Segmentation;MariaDB+Integration;PHP+Frontend+Connectivity" alt="Typing SVG" />
</p>

<p align="center">
  <img src="./assets/divider.svg" alt="Section Divider" width="100%">
</p>

## 🌐 Project Overview

Welcome to the master documentation repository for the **CSP450 Capstone Project**. The explicit goal of this repository is to systematically document my progress and serve as an architectural guide for classmates to understand the implementation mechanics of each lab stage. 

While this repository is publicly accessible to provide a conceptual blueprint for the entire class, the configuration parameters, network scopes, and scripts are tailored specifically to the deployment topology used by my groupmates and me.

---

## ⚠️ Critical Warning for Classmates & Users

> [!WARNING]
> **DO NOT COPY THE IP ADDRESSES OR UIDs IN THIS REPO DIRECTLY!**
> 
> This repository is a blueprint based on a specific lab pod configuration (**Pod C, UID 231**, and partner **UID 217**). If you paste these configurations into your switches or virtual machines without modifying them, your network will break, your classmate's network will break, and you will cause IP conflicts across the rack room.

### ✅ Mandatory Pre-Configuration Steps
Before running any script or applying any configuration from this repository, you **MUST** calculate and substitute your own variables:

1. **Replace UIDs**: Substitute `231` and `217` with your specific student UIDs.
2. **Recalculate Subnets**: Update your private LAN subnets based on your own unique `/26` parameters.
3. **Update MAC Addresses**: Modify all MAC address variables (`static-bind`) with the specific fingerprints of your virtual machine interface cards.

---

<p align="center">
  <img src="./assets/divider.svg" alt="Section Divider" width="100%">
</p>

## 🗺️ How to Use This Guide Effectively

This repository serves as a **conceptual blueprint**, not a copy-paste solution. The configurations, scripts, and network parameters documented here are calculated exclusively from my personal Prep Sheet.

### 🔑 Using the Prep Sheet as Your Reference Key
Since every student has unique UIDs and subnet calculations, you cannot directly apply my commands. Instead, use my documentation to understand the logic behind each step:

| Step | Action |
| :--- | :--- |
| **1️⃣ Open Prep Sheet** | Review `catalan-PrepSheet.txt` located in the root of this repository. |
| **2️⃣ Map the Variables** | When you see an IP address or subnet in my guide (e.g., `172.16.57.192/26`), cross-reference it with the Prep Sheet to see exactly which field it corresponds to (e.g., "Network ID for Subnet #231"). |
| **3️⃣ Substitute Values** | Replace my UID-based values with the equivalent fields from your own completed Prep Sheet. |

💡 **Pro Tip**: If you are confused about where a specific IP address came from in my configuration, always check the Prep Sheet first. It contains the exact formula and source for every network parameter used in this project. Understanding *why* an IP was chosen is more valuable than simply copying it.

---

<p align="center">
  <img src="./assets/divider.svg" alt="Section Divider" width="100%">
</p>

## 🎓 Course Context & Expectations

### The Capstone Challenge
CSP450 is not just another standalone course; it is the culmination of our entire program. As stated in the syllabus, this project is designed to synthesize and apply knowledge acquired from:

* 🌐 **CSN305**: Networking
* 🐧 **OPS345**: Linux Administration
* 🪟 **MST300**: Windows/Server
* 🔒 **SEC320**: Security

### Why This Repository Exists
This guide aims to bridge the gap between theory and practice by providing:
* 🏗️ **Architectural Blueprints**: Methodologies for planning your network before touching a physical cable.
* 🛠️ **Step-by-Step Implementation**: Detailed commands and configurations for Linux and network devices.
* 🔍 **Troubleshooting Logic**: Strategies to diagnose issues when real-world deployments diverge from simulations.

---

<p align="center">
  <img src="./assets/divider.svg" alt="Section Divider" width="100%">
</p>

## 📂 Project Stages

| Stage | Status | Description |
| :--- | :---: | :--- |
| **[Stage Two](./StageTwo/)** | ✅ Completed | Build the network infrastructure layer (OSPF, routing pools, VLAN segmentation, and edge NAT firewalls). |
| **[Stage Three](./StageThree/)** | ✅ Completed | Enterprise MariaDB integration, schema deployment, and PHP frontend connectivity. |

---

<p align="center">
  <img src="./assets/divider.svg" alt="Section Divider" width="100%">
</p>

## 📖 Documentation & Resources

For detailed, step-by-step instructions on how the network layer was planned and executed, please refer to the specific stage documentation folders.

### 📁 Stage Two: Infrastructure Layer
* **[Stage Two README](./StageTwo/README.md)**: Contains the full matrix, infrastructure prerequisite packages, and physical topology blueprints.
* **[Stage Two Templates](./StageTwo/Templates/)**: Switch configurations, firewall rules, and network diagrams for the infrastructure layer.

### 📁 Stage Three: Application Layer
* **[Stage Three Templates](./StageThree/Templates/)**: SQL scripts, PHP source code, and server configuration files for the application layer.

### 📸 Additional Resources
* **Personal Screenshots**: Visual metric captures and packet verification logs used for laboratory report compliance.

---

<p align="center">
  <sub><strong>Last Updated:</strong> July 01, 2026 | <strong>Author:</strong> Ce (UID 231)</sub>
</p>
