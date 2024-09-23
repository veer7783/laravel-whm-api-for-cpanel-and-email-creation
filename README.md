# WHM cPanel Account and Email Creation

This project is designed to automate the creation of cPanel accounts and email addresses using the WHM API. It allows users to create multiple cPanel accounts with corresponding email addresses by sending a single API request.

## Features

- Create cPanel accounts programmatically.
- Add multiple email accounts under a single domain or multiple domains .
- Strong password generation for both cPanel and email accounts.
- Log each action and response for easy debugging and tracking.

## Installation

### 1. Clone the repository
```bash
git clone https://github.com/veer7783/laravel-whm-api-for-cpanel-and-email-creation
cd whm-cpanel-account-email-creator
2. Install dependencies
Make sure you have Composer installed.

bash
Copy code
composer install
3. Configure environment variables
Copy the .env.example file to .env and configure the following environment variables:

env
Copy code
WHM_API_URL=https://your-whm-server:2087
WHM_API_USERNAME=your-whm-username
WHM_API_TOKEN=your-whm-api-token
WHM_ROOT_DOMAIN=your-root-domain
Ensure you have access to the WHM API with a valid token.

4. Run the application
Ensure your local server is set up (e.g., using Laravel's built-in server or another server environment like Nginx or Apache).

bash
Copy code
php artisan serve
Usage
This system accepts a JSON structure to create cPanel accounts and email addresses. Here’s an example request:

API Request Example
Endpoint:

http
POST /api/create-cpanel-account
Request Body:

json

{
    "domains": [
        {
            "domain": "example.com",
            "username": "example_user",
            "plan": "default",
            "emails": [
                {
                    "username": "admin"
                },
                {
                    "username": "support"
                }
            ]
        }
    ]
}
domain: The domain under which the cPanel account is to be created.
username: The username for the cPanel account.
plan: The hosting plan to be applied to the account.
emails: A list of email addresses to be created under the domain.
Response Example
The API will return a JSON response with the results of the operation, indicating whether the account and email creation was successful.

json

{
    "example.com": {
        "status": "success",
        "message": "Account and emails created successfully.",
        "response": { ... },
        "emails": [
            {
                "email": "admin@example.com",
                "status": "success",
                "password": "generated-password"
            },
            {
                "email": "support@example.com",
                "status": "failed",
                "response": { ... }
            }
        ]
    }
}
