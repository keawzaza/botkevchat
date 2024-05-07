'use strict';

const line = require('@line/bot-sdk');
const express = require('express');
const config = require('./config.json');

const client = new line.messagingApi.MessagingApiClient(config);
const mysql = require('mysql');

const mysqlConfig = {
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'project',
};

function unixTimeToTimestamp(unixTimeInMilliseconds) {
  // Create a new Date object with the Unix time in milliseconds
  const date = new Date(unixTimeInMilliseconds);

  // Get the individual components of the date and time
  const year = date.getFullYear();
  const month = (date.getMonth() + 1).toString().padStart(2, '0'); // Months are zero-indexed
  const day = date.getDate().toString().padStart(2, '0');
  const hours = date.getHours().toString().padStart(2, '0');
  const minutes = date.getMinutes().toString().padStart(2, '0');
  const seconds = date.getSeconds().toString().padStart(2, '0');
  const milliseconds = date.getMilliseconds().toString().padStart(3, '0');

  // Construct the timestamp string
  const timestamp = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

  return timestamp;
}


async function insertData(data) {
  const connection = mysql.createConnection(mysqlConfig);

  connection.connect();

  console.log('Inserting data:', data);

  const query = 'INSERT INTO storingChat (line_user_ID, chat, time) VALUES (?, ?, ?)';

  const {  userId } = data.source;
  const {  text } = data.message;
  const {  timestamp } = data;
  //const workStatus = "รับงาน";

  const time = unixTimeToTimestamp(timestamp)
  console.log("time = ",time);

  connection.query(query, [userId, text, time], (error, results) => {
    if (error) {
      console.error('Error inserting data into MySQL:', error);
      throw error;
    }
    console.log('Data inserted successfully into MySQL ',[userId, text, time]);
  });

  connection.end();
}



async function checkDatabaseConnection() {
  const connection = mysql.createConnection(mysqlConfig);

  connection.connect((err) => {
    if (err) {
      console.error('Failed to connect to MySQL:', err);
      return;
    }
    console.log('Connected to MySQL');
  });

  connection.end();
};checkDatabaseConnection()

const app = express();

app.post('/webhook', line.middleware(config), (req, res) => {
  if (!Array.isArray(req.body.events)) {
    return res.status(500).end();
  }
  Promise.all(req.body.events.map(event => {
    insertData(event)
    console.log('event', event);
  }))
    .then(() => res.end())
    .catch((err) => {
      console.error(err);
      res.status(500).end();
    });
});

const port = config.port;
app.listen(port, () => {
  console.log(`listening on ${port}`);
});
