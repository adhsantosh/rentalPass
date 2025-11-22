<!-- footer.php -->
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-left">
            <h3>Rental<span class="rental-text">Pass</span></h3>
            <p>Your premium vehicle rental platform in Nepal.</p>
        </div>
        <div class="footer-center">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="vehicles.php">Vehicles</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
        <div class="footer-right">
            <h4>Contact Us</h4>
            <p>Email: info@rentalpass.com</p>
            <p>Phone: +977-9840592773</p>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> RentalPass. All Rights Reserved.</p>
    </div>
</footer>

<style>
.site-footer {
    background-color: #162447; /* primary dark blue */
    color: #f6f3f0; /* off-white */
    padding: 40px 20px 20px 20px;
    font-family: 'Inter', sans-serif;
}
.footer-container {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 30px;
}
.footer-left h3 {
    font-size: 1.6rem;
    margin-bottom: 10px;
}
.rental-text {
    color: #fddb3a; /* mustard accent */
}
.footer-center h4,
.footer-right h4 {
    font-size: 1.2rem;
    margin-bottom: 10px;
    color: #fddb3a;
}
.footer-center ul {
    list-style: none;
    padding: 0;
}
.footer-center ul li {
    margin-bottom: 6px;
}
.footer-center ul li a,
.footer-right p,
.footer-right a {
    color: #f6f3f0;
    text-decoration: none;
    transition: color 0.3s;
}
.footer-center ul li a:hover,
.footer-right a:hover {
    color: #fddb3a;
}
.social-icons {
    margin-top: 10px;
}
.social-icons a {
    color: #f6f3f0;
    margin-right: 10px;
    font-size: 18px;
    transition: color 0.3s;
}
.social-icons a:hover {
    color: #fddb3a;
}
.footer-bottom {
    text-align: center;
    margin-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.2);
    padding-top: 15px;
    font-size: 0.9rem;
    color: #e8e8f0;
}

/* Responsive */
@media (max-width: 768px) {
    .footer-container {
        flex-direction: column;
        gap: 20px;
    }
    .footer-left, .footer-center, .footer-right {
        text-align: center;
    }
    .social-icons {
        justify-content: center;
        display: flex;
    }
}
</style>
