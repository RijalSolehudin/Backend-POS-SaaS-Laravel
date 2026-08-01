# Role Permission Matrix

Predefined role MVP bersifat system-defined. Tenant user hanya dapat mengelola assignment role; definisi role dan permission tidak dapat dibuat, dihapus, atau diedit dari UI/API.

| Capability | Tenant Owner | Outlet Manager | Cashier |
|---|---:|---:|---:|
| Manage outlets | Yes | No | No |
| Manage outlet user assignment | Yes | No | No |
| Manage predefined tenant roles | Yes | No | No |
| Register POS device | Yes | Yes, assigned outlet only | No |
| Reassign POS device | Yes | No | No |
| Revoke POS device | Yes | No | No |
| Operate POS | Yes | Yes, assigned outlet only | Yes, assigned outlet only |
| Read catalog | Yes | Yes, assigned outlet only | Yes, assigned outlet only |
| Manage catalog | Yes | No | No |

Outlet-scoped capability is always intersected with validated tenant membership, outlet assignment, device binding, token state, and server-side policy. UI visibility is never the only authorization control.
