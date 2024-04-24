const net = require("net");
const fs = require("fs");
const xml2js = require("xml2js");
const path = require('path');

let BASE_URL = "../src";

const server = net.createServer((socket) => {
  logConsoleStatus("Client connected");

  const options = {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false, // Use 24-hour format
    timeZone: "Asia/Dubai",
  };

  const [newDate, newTime] = new Intl.DateTimeFormat("en-US", options)
    .format(new Date())
    .split(",");
  const [m, d, y] = newDate.split("/");
  const formattedDate = `${d.padStart(2, 0)}-${m.padStart(2, 0)}-${y}`;
  //let GlobalformattedDate = `${d.padStart(2, 0)}-${m.padStart(2, 0)}-${y}`;
  const logFilePath = `${BASE_URL}/storage/app/camera/camera-logs-${formattedDate}.csv`;

  console.log(logFilePath);

  socket.on("data", (data) => {
    let TodayDatetime = getTime();
    try {
      const filePath = "./camera-xml-logs/camera-log" + TodayDatetime + ".txt";
      const logFileDir = path.dirname(filePath);
      if (!fs.existsSync(logFileDir)) {
        fs.mkdirSync(logFileDir, { recursive: true });
      }

      const logFileDirPath = path.dirname(logFilePath);
      if (!fs.existsSync(logFileDirPath)) {
        fs.mkdirSync(logFileDirPath, { recursive: true });
      }


      xmlData = data.toString(); // Append data to the image data string
      saveXMlToLog(filePath, xmlData, logFilePath, TodayDatetime);
    } catch (error) {
      console.error("Error processing Data: " + TodayDatetime);
      logConsoleStatus("Error" + error);
    }
  });

  // When the socket connection ends
  socket.on("end", () => { });
});
//console.log(getTime2());
const PORT = 4802; // Port on which the server will listen
server.listen(PORT, () => {
  logConsoleStatus(`Server is listening on port ${PORT}`);
});

function saveXMlToLog(filePath, xmlData, logFilePath, TodayDatetime) {
  fs.appendFile(filePath, xmlData, function (err) {
    if (err) {
      logConsoleStatus("Error" + err);
    } else {
      saveRegisteredMemberstoCSV(xmlData, logFilePath, TodayDatetime, filePath);
    }
  });
}
function saveUNRegisteredMemberstoImage(xmlData, TodayDatetime, deviceId) {
  //logConsoleStatus(`${TodayDatetime} - Reading  unregistered member`);

  let StoragePermission = getPicStoragePermission(deviceId);

  if (StoragePermission) {
    // Regular expression to match content between <?xml version="1.0" encoding="utf-8"?> and </DetectedFaceList>

    let firsttag = `<?xml version="1.0" encoding="utf-8"?><DetectedFaceList>`;
    let endTag = `</DetectedFaceList>`;
    let regex = /<DetectedFaceList>([\s\S]*?)<\/DetectedFaceList>/g;
    let matches;
    while ((matches = regex.exec(xmlData)) !== null) {
      const contentBetweenTags = matches[1];
      //logConsoleStatus(`${TodayDatetime} - XML content reading started`);
      if (contentBetweenTags) {
        let xmlString = firsttag + contentBetweenTags + endTag;

        // Parsing the XML string
        xml2js.parseString(xmlString, (err, result) => {
          if (err) {
            console.error("Error parsing XML:", err);
            logConsoleStatus(`${TodayDatetime} - Error parsing XML: ${err}  `);
          } else {
            const FaceId = result.DetectedFaceList.Face_0[0].FaceID[0] ?? null;
            const Snapshot = result.DetectedFaceList.Face_0[0].Snapshot[0];
            const SnapshotNum =
              result.DetectedFaceList.Face_0[0].FaceSnapFile[0] ?? 0;
            const Quality = result.DetectedFaceList.Face_0[0].Quality[0];

            if (Quality < 0.6) {
              console.log(Quality);
              console.log("Cannot record low quality image");
              return;
            }


            logConsoleStatus(
              `${TodayDatetime} - Saved unregistered member - Face Id:  ${FaceId} - Quality:${Quality}`
            );


            // Convert base64 to a Buffer
            const buffer = Buffer.from(Snapshot, "base64");

            let filePath = `${BASE_URL}/public/camera-unregsitered-faces-logs/${FaceId}/${SnapshotNum}.jpg`;
            const logFileDir = path.dirname(filePath);
            if (!fs.existsSync(logFileDir)) {
              fs.mkdirSync(logFileDir, { recursive: true });
            }

            fs.writeFileSync(filePath, buffer);
          }
        });

        // logConsoleStatus(`${TodayDatetime} - XML content reading completed`);
      } else {
        logConsoleStatus(
          `${TodayDatetime} - Saving unregistered Failed . No Content `
        );
      }
    } //whilre
  } else {
    // logConsoleStatus(
    //   `${TodayDatetime} - -------- No permission to save unregistered`
    // );
  }

  // } catch (error) {
  //   console.error("Error while saving image:" + TodayDatetime);
  //   logConsoleStatus("Error" + error);
  // }
}

function getPicStoragePermission(device_id) {
  // Example usage

  return true;

  const filePath = `${BASE_URL}/storage/app/devices_list.json`;

  const logFileDir = path.dirname(filePath);
  if (!fs.existsSync(logFileDir)) {
    fs.mkdirSync(logFileDir, { recursive: true });
  }

  const content = fs.readFileSync(filePath, "utf8");

  let data1 = JSON.parse(content);
  let specificValue = data1.find((e) => e.device_id == device_id);

  return specificValue ? specificValue.camera_save_images : false;
}

function getDate() {
  const options = {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false, // Use 24-hour format
    timeZone: "Asia/Dubai",
  };

  const [newDate, newTime] = new Intl.DateTimeFormat("en-US", options)
    .format(new Date())
    .split(",");
  const [m, d, y] = newDate.split("/");
  return (formattedDate = `${d.padStart(2, 0)}-${m.padStart(2, 0)}-${y}`);
}
function saveRegisteredMemberstoCSV(
  xmlData,
  logFilePath,
  TodayDatetime,
  filePath
) {

  try {
    const xmlData = fs.readFileSync(filePath, "utf8");

    let FaceIDArray = checkMultipleOccurrences(xmlData, "FaceID");
    let ClarityArray = checkMultipleOccurrences(xmlData, "Clarity");
    let AgeArray = checkMultipleOccurrences(xmlData, "Age");
    let QualityArray = checkMultipleOccurrences(xmlData, "Quality");
    let GenderArray = checkMultipleOccurrences(xmlData, "Gender");
    let SimilarityArray = checkMultipleOccurrences(xmlData, "Similarity");
    let RegisterIdArray = checkMultipleOccurrences(xmlData, "RegisteredID");
    let macArray = checkMultipleOccurrences(xmlData, "MACAddress");
    let DeviceIDArray = checkMultipleOccurrences(xmlData, "DeviceID");
    let CardNumArray = checkMultipleOccurrences(xmlData, "CardNum");
    let TimeArray = checkMultipleOccurrences(xmlData, "Time");

    if (CardNumArray.length == 0) {
      saveUNRegisteredMemberstoImage(xmlData, TodayDatetime, DeviceIDArray[0]);
    }
    CardNumArray.forEach((element, arrayCounter) => {
      try {
        let UserCode = CardNumArray[arrayCounter];
        let DeviceID = DeviceIDArray[arrayCounter];
        let RecordDate = getTime2();
        if (TimeArray.length > 0) {
          RecordDate = TimeArray[arrayCounter];
          if (RecordDate) RecordDate = formatdate(RecordDate);
        }
        let RecordNumber = RegisterIdArray[arrayCounter];
        let FaceID = FaceIDArray[arrayCounter];
        let Clarity = ClarityArray[arrayCounter];
        let Age = AgeArray[arrayCounter];
        let Quality = QualityArray[arrayCounter];
        let Gender = GenderArray[arrayCounter];
        let Similarity = SimilarityArray[arrayCounter];

        if (UserCode > 0) {
          const logEntry = `${UserCode},${DeviceID},${RecordDate},${RecordNumber},${FaceID},${Clarity},${Age},${Quality},${Gender},${Similarity}`;
          fs.appendFileSync(logFilePath, logEntry + "\n");

          logConsoleStatus("Registered Log recorded " + logEntry);


          const logFileDir = path.dirname(logFilePath);
          if (!fs.existsSync(logFileDir)) {
            fs.mkdirSync(logFileDir, { recursive: true });
          }

          fs.chmod(logFilePath, 0o666, () => {
            fs.appendFileSync(logFilePath, logEntry + "\n");

            logConsoleStatus("Registered Log recorded " + logEntry);
          });
        } else {
        }
      } catch (error) {
        console.error(
          "Error processing message:",
          TodayDatetime,
          error.message,
          RegisterIdArray,
          macArray,
          CardNumArray,
          TimeArray
        );
      }

    });
  } catch (error) {
    console.error("Error while saveRegisteredMemberstoCSV:" + TodayDatetime);
    logConsoleStatus("Error" + error);
  }
}

function logConsoleStatus(message) {
  console.log(message);
  // const logFilePath = `camera/camera-live-status-${getDate()}.log`;
  // fs.appendFileSync(logFilePath, message + "\n");
}
function formatdate(originalDateTime) {
  originalDateTime = originalDateTime.replace("T", " ");
  originalDateTime = originalDateTime.replace("Z", "");

  const date = new Date(originalDateTime);
  const formattedDateTime =
    date.getFullYear() +
    "-" +
    String(date.getMonth() + 1).padStart(2, "0") +
    "-" +
    String(date.getDate()).padStart(2, "0") +
    " " +
    String(date.getHours()).padStart(2, "0") +
    ":" +
    String(date.getMinutes()).padStart(2, "0") +
    ":" +
    String(date.getSeconds()).padStart(2, "0");

  return formattedDateTime;
}
function checkMultipleOccurrences(sentence, tagName) {
  let matchExpression = new RegExp("<" + tagName + "[^\\s]+", "g");

  let array = sentence.match(matchExpression);

  return readElementValue(array, tagName);
}

function readElementValue(inputarray, tagName) {
  let returnArray = [];
  if (inputarray) {
    inputarray.forEach((element) => {
      let xmlSnippet = element;

      let matchExpression = new RegExp(
        "<" + tagName + ">(.*?)</" + tagName + ">"
      );

      match = xmlSnippet.match(matchExpression);

      if (match && match[1]) {
        returnArray.push(match[1])
      }

    });
  }
  return returnArray;
}
function getTime2() {
  // Get the current UTC date and time in ISO format

  // return formattedDateTime;
  let date_ob = new Date();

  let utc = date_ob.getTime();

  // Define the time difference in hours for GMT+4
  let gmt_offset = 0;

  // Calculate the time in milliseconds for GMT+4 by adding the offset
  let gmt_time = utc + gmt_offset * 60 * 60 * 1000;

  // Create a new Date object for GMT+4 time
  date_ob = new Date(gmt_time);
  // current date
  // adjust 0 before single digit date
  let date = ("0" + date_ob.getDate()).slice(-2);
  // current month
  let month = ("0" + (date_ob.getMonth() + 1)).slice(-2);
  // current year
  let year = date_ob.getFullYear();
  // current hours
  let hours = ("0" + date_ob.getHours()).slice(-2);
  // current minutes
  let minutes = ("0" + date_ob.getMinutes()).slice(-2);
  // current seconds
  let seconds = date_ob.getSeconds();
  // prints date in YYYY-MM-DD format
  //logConsoleStatus(year + "-" + month + "-" + date);
  // prints date & time in YYYY-MM-DD HH:MM:SS format
  return (
    year +
    "-" +
    month +
    "-" +
    date +
    " " +
    hours +
    ":" +
    minutes +
    ":" +
    seconds
  );
}
function getTime() {
  let date_ob = new Date();

  let utc = date_ob.getTime();

  // Define the time difference in hours for GMT+4
  let gmt_offset = 0;

  // Calculate the time in milliseconds for GMT+4 by adding the offset
  let gmt_time = utc + gmt_offset * 60 * 60 * 1000;

  // Create a new Date object for GMT+4 time
  date_ob = new Date(gmt_time);
  // current date
  // adjust 0 before single digit date
  let date = ("0" + date_ob.getDate()).slice(-2);

  // current month
  let month = ("0" + (date_ob.getMonth() + 1)).slice(-2);

  // current year
  let year = date_ob.getFullYear();

  // current hours
  let hours = date_ob.getHours();

  // current minutes
  let minutes = date_ob.getMinutes();

  // current seconds
  let seconds = date_ob.getSeconds();

  // prints date in YYYY-MM-DD format
  //logConsoleStatus(year + "-" + month + "-" + date);

  // prints date & time in YYYY-MM-DD HH:MM:SS format
  return (
    year +
    "-" +
    month +
    "-" +
    date +
    " " +
    hours +
    "-" +
    minutes +
    "-" +
    seconds
  );
}
