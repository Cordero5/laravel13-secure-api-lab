# Laboratory Activity Submission: Secure API Design and Token-Based Authentication

**Student Name:** Alfred B. Cordero 
**Course:** Integrative Programming & Technologies II  
**Module:** Module 5 — Lesson 1  

## Screenshots Checklist
- `01-public-api-200.png` — Public endpoint verification (`200 OK`)
- `02-register-201.png` — User registration (`201 Created`)
- `03-login-200.png` — User login & token generation (`200 OK`)
- `04-profile-no-token-401.png` — Protected access without token (`401 Unauthorized`)
- `05-profile-with-token-200.png` — Protected profile access with Bearer token (`200 OK`)
- `06-invalid-login-422.png` — Invalid login attempt (`422 Unprocessable Content`)
- `07-logout-200.png` — Logout and token revocation (`200 OK`)
- `08-revoked-token-401.png` — Profile access with revoked token (`401 Unauthorized`)

## Reflection
A `401 Unauthorized` response indicates that a request lacks valid authentication credentials, whereas a successful authenticated profile response (`200 OK`) validates the token and returns the user's account details. When a user logs out, their active personal access token is deleted from the database and revoked. Consequently, any subsequent attempts to access protected endpoints with that same token will be rejected with a `401 Unauthorized` error.