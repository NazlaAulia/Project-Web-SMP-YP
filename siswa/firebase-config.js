// firebase-config.js

import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
import { getStorage } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-storage.js";

const firebaseConfig = {
  apiKey: "AIzaSyBT5dv7OzGuTuwXZDvwbyqITZ4M-KaF1kY",
  authDomain: "smpyp17.firebaseapp.com",
  projectId: "smpyp17",
  storageBucket: "smpyp17.firebasestorage.app",
  messagingSenderId: "74715637846",
  appId: "1:74715637846:web:2e3ed5dc6bf4079aa1b3f7",
  measurementId: "G-NEKTB23085"
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);
const auth = getAuth(app);
const storage = getStorage(app);

export { app, db, auth, storage };