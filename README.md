# PHP News Portal

A dynamic, feature-rich news portal built with PHP, MySQL, and modern web technologies.

## Description

PHP News Portal is a comprehensive web application designed to serve as a flexible and scalable platform for publishing and managing news content. Built with PHP and MySQL, this project incorporates best practices in web development and offers a wide range of features for both users and administrators.

## Key Features

- User authentication and role-based access control
- Article management system with categories and tags
- Comment system with moderation capabilities
- Responsive design for optimal viewing on various devices
- Admin dashboard for content and user management
- Search functionality for articles and users
- RSS feed for easy content syndication
- Newsletter subscription system
- Popular articles tracking
- Sitemap generation for improved SEO

## Technical Highlights

- Secure user authentication and authorization
- PDO for database interactions to prevent SQL injection
- XSS protection through proper output escaping
- CSRF protection for form submissions
- Pagination for improved performance with large datasets
- Caching system to reduce database load
- Email integration for notifications and password resets

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/budisangster/php-news-portal.git
   ```
2. Create a MySQL database and import the provided SQL schema.
3. Copy `config/database.example.php` to `config/database.php` and update with your database credentials.
4. Configure your web server to point to the project's public directory.
5. Install dependencies:
   ```
   composer install
   ```
6. Ensure proper file permissions are set.

## Usage

- Access the frontend by navigating to http://news-portal-website.great-site.net/ in your web browser.
- Admin panel can be accessed at http://news-portal-website.great-site.net/admin
- To test the admin functionality, use the following credentials:
  - Username: admin
  - Password: admin123

Please note that these are test credentials and should be changed in a production environment.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is open source and available under the [MIT License](LICENSE).

## Contact

If you have any questions, feel free to reach out to me at https://bit.ly/faizmuh
