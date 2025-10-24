</div>
            </div>
        </div>
    <script src="js/jquery.slim.min.js"></script>
    
    <script src="js/popper.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="js/Chart.min.js"></script>
    
    <script src="js/feather.min.js"></script> 
    
    <script>
        // Toggle the sidebar functionality (Mobile/Desktop)
        document.getElementById("sidebarToggle").addEventListener("click", function(e) {
            e.preventDefault();
            document.getElementById("wrapper").classList.toggle("toggled");
        });
        
        // Initialize Feather Icons (if still used)
        feather.replace();
    </script>
</body>
</html>