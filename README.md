# Laravel 13 Secure API with Role-Based Access Control (RBAC)

Course: Integrative Programming & Technologies II  
Module 5 — Lesson 2: Authorization and Role-Based Access Control  

## 💡 Reflection Questions

### 1. What is the difference between authentication and authorization?
* **Authentication** is the process of verifying *who* a user is (e.g., logging in via `/api/login` using valid credentials to receive a Sanctum token). 
* **Authorization** is the process of verifying *what* an authenticated user is allowed to do or access (e.g., checking if a user has the `admin` role or `student` role via custom middleware before granting access to specific endpoints).

### 2. Why does a student receive `403` when accessing the admin dashboard, but a request without a token receives `401`?
* A request **without a token** fails at the authentication layer (`auth:sanctum`), resulting in **`401 Unauthenticated`** because the system cannot identify who is making the request.
* A request **with a student token** successfully authenticates the user, but the custom admin middleware (`AdminMiddleware`) determines that the user's role does not match the required privileges, resulting in **`403 Forbidden`** because the identity is known, but permission is denied.

This project implements token-based authentication via Laravel Sanctum and custom role-based access control (RBAC) middleware (`CheckRole` and `AdminMiddleware`) to protect API endpoints in Laravel 13.

---

## 🚀 Local Setup & Installation

1. **Clone the Repository:**
   ```bash
   git clone [https://github.com/Cordero5/laravel13-secure-api-lab.git]
            (https://github.com/Cordero5/laravel13-secure-api-lab.git)
   cd laravel13-secure-api-lab
   cd demo-api