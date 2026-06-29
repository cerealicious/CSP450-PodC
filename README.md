# 🌐 CSP450

Welcome to the master documentation repository for my CSP450 project. The explicit goal of this repository is to systematically document my progress and serve as an architectural guide for my classmates to understand the implementation mechanics of each lab stage. While this repository is publicly accessible to provide a conceptual blueprint for the entire class, the configuration parameters, network scopes, and scripts are tailored specifically to the deployment topology used by my groupmates and me.

---

## ⚠️ CRITICAL WARNING FOR CLASSMATES / USERS
> **🛑 DO NOT COPY THE IP ADDRESSES OR UIDs IN THIS REPO BLINDLY!**
> 
> This repository is a blueprint based on a specific lab pod configuration (**Pod C**, **UID 231**, and partner **UID 217**). If you paste these configurations into your switches or virtual machines without modifying them, **your network will break, your classmate's network will break, and you will cause IP conflicts across the rack room.**
>
> BEFORE running any script or applying any configuration from this repository, you **MUST** calculate and substitute your own variables:
> * Replace **`231`** and **`217`** with your specific student UIDs.
> * Recalculate your private LAN subnets based on your own unique `/26` parameters.
> * Update all MAC address variables (`static-bind`) with the specific fingerprints of your virtual machine interface cards.

---

## 📂 Project Stages

| Stage | Status | Description | Link |
| :--- | :--- | :--- | :--- |
| **Stage Two** | ✅**Completed** | Build the network infrastructure layer (OSPF, routing pools, VLAN segmentation, and edge NAT firewalls | [View Stage Two Folder](./StageTwo/) |
| **Stage Three** | 🚧**In-Progress** | Enterprise MariaDB integration, schema deployment, and PHP frontend connectivity. | [View Stage Three Folder](./StageThree/) |

## 📖 Documentation
For detailed, step-by-step instructions on how the network layer was planned and executed, please refer to the specific stage documentation folder:
* **[Stage Two README](./StageTwo/README.md)**: Contains the full matrix, infrastructure prerequisite packages, and physical topology blueprints.

## 📁 Additional Resources
* **[Templates](./Templates/)**: Configuration files used during the project deployment pipeline.
* **[VM Screenshots](./VM%20SS/)**: Visual metric captures and packet verification logs used for laboratory report compliance.

---
*Last Updated: June 29, 2026*
