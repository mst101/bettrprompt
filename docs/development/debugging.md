# Debugging Guide

## PostgreSQL Permissions
Error:
```
SQLSTATE[42501]: Insufficient privilege
```
Fix:
```sql
GRANT ALL PRIVILEGES ON SCHEMA public TO your_user;
```
