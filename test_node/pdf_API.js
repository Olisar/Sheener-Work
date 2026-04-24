/* File: sheener/test_node/pdf_API.js */
require("dotenv").config({ path: "./API.env" });

const fs = require("fs");
const path = require("path");
const pdfParse = require("pdf-parse");
const { OpenAIApi } = require("openai");
const winston = require("winston");

// Configure logging with winston
const logger = winston.createLogger({
    level: "info",
    format: winston.format.combine(
        winston.format.timestamp(),
        winston.format.printf(({ timestamp, level, message }) => `${timestamp} - ${level.toUpperCase()}: ${message}`)
    ),
    transports: [
        new winston.transports.File({ filename: "process_pdfs.log" }),
        new winston.transports.Console(),
    ],
});

// Load OpenAI API Key
const openai = new OpenAIApi({
    apiKey: process.env.OPENAI_API_KEY,
});

if (!process.env.OPENAI_API_KEY) {
    logger.error("OpenAI API Key not found. Please set it in the API.env file.");
    process.exit(1);
}

// Function to extract text from PDF
async function extractTextFromPdf(pdfPath) {
    try {
        const dataBuffer = fs.readFileSync(pdfPath);
        const data = await pdfParse(dataBuffer);
        logger.info(`Extracted text from PDF: ${pdfPath}`);
        return data.text;
    } catch (error) {
        logger.error(`Error extracting text from PDF ${pdfPath}: ${error}`);
        return null;
    }
}

// Function to analyze PDF content using OpenAI API
async function analyzePdfWithOpenAI(pdfContent) {
    try {
        const response = await openai.createChatCompletion({
            model: "gpt-4",
            messages: [
                { role: "system", content: "Extract key SOP data in JSON format." },
                { role: "user", content: pdfContent },
            ],
        });
        const responseContent = response.data.choices[0].message.content;
        logger.info("Analyzed PDF content with OpenAI API successfully.");
        return responseContent;
    } catch (error) {
        logger.error(`Error analyzing text with OpenAI: ${error}`);
        return null;
    }
}

// Function to save JSON data to a file
async function saveJsonToFile(sopData, outputDir, fileName) {
    try {
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }
        const outputPath = path.join(outputDir, `${fileName}.json`);
        fs.writeFileSync(outputPath, JSON.stringify(sopData, null, 4), "utf-8");
        logger.info(`Saved JSON data to file: ${outputPath}`);
    } catch (error) {
        logger.error(`Error saving JSON data to file for ${fileName}: ${error}`);
    }
}

// Function to process PDF files in a directory
async function processPdfs(inputDir, outputDir) {
    try {
        const files = fs.readdirSync(inputDir);
        for (const file of files) {
            if (path.extname(file) === ".pdf") {
                const pdfPath = path.join(inputDir, file);
                logger.info(`Processing file: ${file} from ${inputDir}`);

                // Extract text from PDF
                const pdfContent = await extractTextFromPdf(pdfPath);
                if (!pdfContent) {
                    logger.error(`Failed to extract text from ${file}`);
                    continue;
                }

                // Analyze PDF content using OpenAI API
                const apiResponse = await analyzePdfWithOpenAI(pdfContent);
                if (!apiResponse) {
                    logger.error(`Failed to analyze text for ${file}`);
                    continue;
                }

                // Parse the API response and save it as JSON
                try {
                    const sopData = JSON.parse(apiResponse);
                    sopData.File = file; // Add the file name to the JSON
                    await saveJsonToFile(sopData, outputDir, path.parse(file).name);
                } catch (error) {
                    logger.error(`Error parsing or saving JSON for ${file}: ${error}`);
                    logger.error(`API response: ${apiResponse}`);
                }
            }
        }
    } catch (error) {
        logger.error(`Error processing directory ${inputDir}: ${error}`);
    }
}

// Main function
async function main() {
    const inputDirectories = [
        "C:/Documents/EHS",
        "C:/Documents/ES",
        "C:/Documents/QA QC",
    ];
    const outputDirectory = "C:/Documents/JSON_Output";

    for (const directory of inputDirectories) {
        if (fs.existsSync(directory)) {
            await processPdfs(directory, outputDirectory);
        } else {
            logger.warn(`Directory not found: ${directory}`);
        }
    }

    logger.info("Processing completed.");
}

main().catch((error) => logger.error(`Fatal error: ${error}`));
