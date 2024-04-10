# Shopify Google Sheet App

This is a Laravel-based application that fetches products from a Shopify store and adds them to a Google Sheets account.

## Getting Started

To run this project, follow these steps:

1. Clone the project to your local machine.
2. Navigate to the project folder using the `cd` command on your command prompt or terminal.
3. Install the project dependencies using the `composer install` command.
4. Copy the `.env.example` file to `.env` in the root folder. You can use the command `copy .env.example .env` on Windows or `cp .env.example .env` on Ubuntu.
5. Update the `.env` file with your database name, username, and password.
6. Add your Google and Shopify variables to the `.env` file.
7. Generate a new application key using the `php artisan key:generate` command.
8. Run the migrations using the `php artisan migrate` command.
9. Start the Laravel development server using the `php artisan serve` command.
10. Access the application at <http://localhost:8000/>.

## Frontend

The frontend of this application is located in a separate repository. You can find it at <https://github.com/Akshat-Pandey16/ShopifyGoogle-Frontend>.

## License

This project is open-source and licensed under the MIT License.