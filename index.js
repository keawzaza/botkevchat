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
  database: 'line',
};

async function insertData(data) {
  const connection = mysql.createConnection(mysqlConfig);

  connection.connect();

  console.log('Inserting data:', data);

  const query = 'INSERT INTO storingChat (line_user_ID, chat, time) VALUES (?, ?, ?)';

  const {  userId } = data.source;
  const {  text } = data.message;
  const {  timestamp } = data;

  connection.query(query, [userId, text, timestamp], (error, results) => {
    if (error) {
      console.error('Error inserting data into MySQL:', error);
      throw error;
    }
    console.log('Data inserted successfully into MySQL ',[userId, text, timestamp]);
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
