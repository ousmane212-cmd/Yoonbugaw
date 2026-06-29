
        
    
    <footer class="dashboard-footer">
        <div class="footer-container">
            <div class="footer-left">
                <p>&copy; <?php echo date('Y'); ?> <strong>Yoon bu Gaw</strong>. Tous droits réservés.</p>
            </div>
            <div class="footer-right">
                <ul class="footer-links">
                    <li><a href="#aide">Centre d'aide</a></li>
                    <li><a href="#confidentialite">Confidentialité</a></li>
                    <li><a href="#conditions">Conditions d'utilisation</a></li>
                </ul>
            </div>
        </div>
    </footer>

  
    <style>
        .dashboard-footer {
            background-color: var(--white);
            border-top: 1px solid var(--border-color);
            padding: 16px 24px;
            margin-left: var(--sidebar-width); /* Aligné avec le contenu principal */
            margin-top: auto; /* Pousse le footer en bas si vous utilisez un flexbox layout */
            transition: margin-left 0.2s ease;
        }

        .footer-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .footer-left p {
            font-size: 13px;
            color: var(--text-gray);
            margin: 0;
        }

        .footer-right .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 20px;
        }

        .footer-right .footer-links a {
            font-size: 13px;
            color: var(--text-gray);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .footer-right .footer-links a:hover {
            color: var(--accent); /* Devient vert au survol */
        }

        /* Responsive : s'adapte lorsque la sidebar passe en haut/disparaît */
        @media (max-width: 991px) {
            .dashboard-footer {
                margin-left: 0; /* Prend toute la largeur sur tablette/mobile */
                padding: 16px;
            }
            .footer-container {
                flex-direction: column;
                text-align: center;
            }
            .footer-right .footer-links {
                justify-content: center;
                gap: 15px;
            }
        }
    </style>

   
    <script>
       
        document.addEventListener('DOMContentLoaded', function() {
            const closeBtn = document.querySelector('.form-close');
            const overlay = document.getElementById('form-overlay');
            const cancelBtn = document.querySelector('.btn-cancel');

            if (closeBtn && overlay) {
                closeBtn.addEventListener('click', () => overlay.classList.remove('open'));
            }
            if (cancelBtn && overlay) {
                cancelBtn.addEventListener('click', () => overlay.classList.remove('open'));
            }
        });
    </script>
</body>
</html>