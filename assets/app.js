import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
function showDateTime() {
  const date = new Date();
  const hours = date.getHours();
  const minutes = date.getMinutes();
  const seconds = date.getSeconds();
  const day = date.getDate();
  const month = date.getMonth() + 1;
  const year = date.getFullYear();

  const dateTime = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
  document.getElementById("date").innerText = dateTime;
}

showDateTime();
setInterval(showDateTime, 1000); // Mettre à jour toutes les secondes