// Hamburger Menu Toggle
const hamburger = document.querySelector(".hamburger")
const navMenu = document.querySelector(".nav-menu")
const headerActions = document.querySelector(".header-actions")

hamburger.addEventListener("click", () => {
  navMenu.style.display = navMenu.style.display === "flex" ? "none" : "flex"
  headerActions.style.display = headerActions.style.display === "flex" ? "none" : "flex"
})

// Navigation Links - Active State
const navLinks = document.querySelectorAll(".nav-link")

navLinks.forEach((link) => {
  link.addEventListener("click", function () {
    navLinks.forEach((l) => l.classList.remove("active"))
    this.classList.add("active")
    navMenu.style.display = "none"
    headerActions.style.display = "none"
  })
})

// Add to Cart Button
const addButtons = document.querySelectorAll(".btn-add")

addButtons.forEach((button) => {
  button.addEventListener("click", () => {
    alert("Item added to cart!")
  })
})

// Smooth Scroll
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault()
    const target = document.querySelector(this.getAttribute("href"))
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      })
    }
  })
})

// Language Selector
const languageSelect = document.querySelector(".language-select")

languageSelect.addEventListener("change", function () {
  const lang = this.value
  console.log("Language changed to:", lang)
  // Add language change logic here
})

console.log("Ateye albailk - Landing Page Loaded")
