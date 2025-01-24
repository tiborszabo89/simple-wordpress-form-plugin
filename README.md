# Contact Form Manager

**Contact Form Manager** is a lightweight and secure WordPress plugin that allows you to add a custom contact form to your site. It saves all submitted entries in the database and provides an admin interface for managing them. The plugin uses secure WordPress best practices to handle data and prevents duplicate submissions with the Post/Redirect/Get (PRG) pattern.

## Features

- Simple contact form with fields for Name, Email, and Message.
- Stores form submissions in a custom database table.
- Admin dashboard interface to view and delete form entries.
- Uses the Post/Redirect/Get (PRG) pattern to prevent duplicate submissions.
- Easy to use with a shortcode.

## Installation

1. Download the plugin files and upload the `contact-form-manager` folder to your WordPress site in the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Add the form to a page or post using the `[cfm_contact_form]` shortcode.

## Usage

### Adding the Form
To display the form on a page or post, insert the following shortcode:

[cfm_contact_form]


### Viewing Entries
Go to **Contact Entries** in the WordPress admin dashboard to view all submitted entries.

### Deleting Entries
You can delete specific entries by clicking the **Delete** button in the admin interface.

## Developer Notes

### Table Creation
The plugin creates a custom database table named `<prefix>_contact_entries` during activation, where `<prefix>` is your WordPress table prefix (e.g., `wp_`).

### Security Measures
- Input sanitization using WordPress functions (`sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field`).
- Nonce verification to protect against CSRF attacks.
- Prepared statements for all database queries to prevent SQL injection.

### Post/Redirect/Get (PRG) Pattern
The plugin implements the PRG pattern to prevent form resubmission on page refresh.


## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Contributing

Feel free to fork this repository and submit pull requests. Contributions are welcome!

1. Fork the repository.
2. Create a new branch: `git checkout -b feature-name`.
3. Commit your changes: `git commit -m "Add feature"`.
4. Push to the branch: `git push origin feature-name`.
5. Open a pull request.

## License

This project is licensed under the MIT License. 

---

### Author

**Tibor Szabó**  

