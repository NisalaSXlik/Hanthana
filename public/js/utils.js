// js/utils.js

/**
 * Formats a Date object into a "MMM dd, yyyy" string.
 * @param {Date} date
 * @returns {string}
 */
function formatDate(date) {
  if (!date) return "N/A"
  const options = { year: "numeric", month: "short", day: "numeric" }
  return date.toLocaleDateString("en-US", options)
}

/**
 * Formats a time string (HH:MM) into a more readable format (e.g., "9:00 AM").
 * @param {string} timeString
 * @returns {string}
 */
function formatTime(timeString) {
  if (!timeString) return "N/A"
  const [hours, minutes] = timeString.split(":").map(Number)
  const date = new Date()
  date.setHours(hours, minutes)
  return date.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", hour12: true })
}

/**
 * Formats a date string (YYYY-MM-DD) and time strings (HH:MM) into a combined readable format.
 * @param {string} dateStr - YYYY-MM-DD
 * @param {string} startTimeStr - HH:MM
 * @param {string} endTimeStr - HH:MM
 * @returns {string}
 */
function formatEventDateTime(dateStr, startTimeStr, endTimeStr) {
  const date = dateStr ? new Date(dateStr + "T00:00:00") : null
  const formattedDate = date ? formatDate(date) : "TBD"
  const formattedStartTime = startTimeStr ? formatTime(startTimeStr) : "TBD"
  const formattedEndTime = endTimeStr ? formatTime(endTimeStr) : "TBD"

  if (formattedStartTime === "TBD" && formattedEndTime === "TBD") {
    return formattedDate
  }
  return `${formattedDate} ${formattedStartTime} - ${formattedEndTime}`
}

/**
 * Opens a modal by adding the 'active' class.
 * @param {HTMLElement} modalElement
 */
function openModal(modalElement) {
  modalElement.classList.add("active")
  document.body.style.overflow = "hidden" // Prevent scrolling background
}

/**
 * Closes a modal by removing the 'active' class.
 * @param {HTMLElement} modalElement
 */
function closeModal(modalElement) {
  modalElement.classList.remove("active")
  document.body.style.overflow = "" // Restore scrolling
}

/**
 * Creates an HTML element with optional classes, text content, and attributes.
 * @param {string} tagName - The tag name of the element (e.g., 'div', 'p', 'button').
 * @param {string[]} [classNames=[]] - An array of class names to add.
 * @param {string} [textContent=''] - The text content of the element.
 * @param {Object} [attributes={}] - An object of attributes to set (e.g., { id: 'myId', 'data-value': '123' }).
 * @returns {HTMLElement} The created HTML element.
 */
function createElement(tagName, classNames = [], textContent = "", attributes = {}) {
  const element = document.createElement(tagName)
  if (classNames.length > 0) {
    element.classList.add(...classNames)
  }
  if (textContent) {
    element.textContent = textContent
  }
  for (const key in attributes) {
    if (attributes.hasOwnProperty(key)) {
      element.setAttribute(key, attributes[key])
    }
  }
  return element
}
