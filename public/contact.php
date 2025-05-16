<?php include('../includes/header.php'); ?>

<main class="contact-page">
    <section class="contact-header">
        <h1>Contact Us</h1>
        <p>Have questions? Our team is ready to help!</p>
    </section>

    <div class="contact-container">
        <form class="contact-form" method="POST" action="process_contact.php">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="subject">Subject</label>
                <select id="subject" name="subject">
                    <option value="booking">Booking Inquiry</option>
                    <option value="support">Customer Support</option>
                    <option value="partnership">Partnership Opportunity</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            
            <button type="submit" class="btn">Send Message</button>
        </form>

    
    </div>
</main>

<?php include('../includes/footer.php'); ?>