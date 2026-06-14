# Ubuntu Corporate Server Netplan Configuration
**Target Location:** `/etc/netplan/dhcpAssignment.yaml` on the **Ubuntu Server VM**.

Apply this code layout using `sudo netplan apply`. It sets up interface `ens33` to request configuration parameters through DHCP. The central switch engine maps its hardware network address string directly back to your isolated profile boundary, binding your reserved static address boundary profile (`172.16.57.254`).

```yaml
network:
  version: 2
  ethernets:
    # ens33 faces your local office LAN switch port inside VMnet5
    ens33:
      dhcp4: true                      # ----> Listens for Switch Automated MAC Bound Static Allocation
