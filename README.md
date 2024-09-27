# Food Chef Cafe Management System

A comprehensive PHP-based web application for managing cafe and restaurant operations with both user-facing frontend and admin panel.

## 🍽️ Features

### User Features
- **Responsive Design**: Modern, mobile-friendly interface
- **Menu Display**: Beautiful food gallery with descriptions
- **About Section**: Restaurant information and story
- **Services**: Available services and offerings
- **Team**: Staff profiles and information
- **Contact**: Contact form and location details
- **Photo Gallery**: Restaurant ambiance and food photos

### Admin Features
- **Dashboard**: Complete admin control panel
- **Content Management**: Add, edit, delete content
- **Food Management**: Manage menu items and categories
- **Banner Management**: Update homepage banners
- **Team Management**: Manage staff profiles
- **Services Management**: Update service offerings
- **Photo Gallery**: Upload and manage images
- **Contact Management**: Handle contact information

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Custom MVC Architecture
- **Styling**: Bootstrap, Font Awesome
- **Server**: Apache/Nginx

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP/MAMP (for local development)

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/food-chef-cafe-management.git
   cd food-chef-cafe-management
   ```

2. **Setup Database**
   - Open phpMyAdmin
   - Create a new database named `project`
   - Import `Food-Chef-Cafe-Management-master/database/project.sql`

3. **Configure Database**
   - Edit `Food-Chef-Cafe-Management-master/config/config.php`
   - Update database credentials if needed

4. **Access the Application**
   - User Frontend: `http://localhost/project`
   - Admin Panel: `http://localhost/project/admin`
   - Admin Login: username: `admin`, password: `admin123`

## 📁 Project Structure

```
Food-Chef-Cafe-Management-master/
├── admin/                 # Admin panel
│   ├── modules/          # Admin modules
│   ├── public/           # Admin assets
│   └── config/           # Admin configuration
├── config/               # Main configuration
├── database/             # Database files
├── libs/                 # Core libraries
├── modules/              # Frontend modules
├── public/               # Public assets
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   ├── images/          # Images
│   └── fonts/           # Font files
└── index.php            # Main entry point
```

## 🔧 Configuration

### Database Configuration
Edit `config/config.php`:
```php
define('BASEURL','http://localhost/project/');
define('HOSTNAME','localhost');
define('USERNAME','root');
define('PASSWORD','');
define('DB','project');
```

## 👥 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📝 License

This project is licensed under the MIT License.

## 🤝 Support

For support and questions, please open an issue on GitHub.

## 🙏 Acknowledgments

- Bootstrap for responsive design
- Font Awesome for icons
- jQuery for JavaScript functionality
