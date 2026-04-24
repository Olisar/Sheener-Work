<?php
/* File: sheener/api/chat_ai.php */

/**
 * Chat AI API Endpoint
 * Handles chat queries and integrates with Google Gemini API
 */

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them
ini_set('log_errors', 1);

// Register shutdown function to catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'error' => 'Fatal error: ' . $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
});

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../php/database.php';

// Set headers first
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    // Check for JSON parsing errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON: ' . json_last_error_msg(),
            'raw_input' => substr($rawInput, 0, 100)
        ]);
        exit;
    }

    if (!$input || !isset($input['query']) || empty(trim($input['query']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Query is required',
            'received' => $input
        ]);
        exit;
    }

    $query = trim($input['query']);
    $pageContext = isset($input['pageContext']) ? trim($input['pageContext']) : '';
    $clientData = isset($input['clientData']) ? trim($input['clientData']) : '';

    // Detect if query requires data analysis
    $requiresData = detectDataQuery($query);
    $contextData = null;

    if ($requiresData) {
        try {
            // Fetch relevant data from backend
            $contextData = fetchRelevantData($query);
            if ($contextData === null) {
                error_log('fetchRelevantData returned null for query: ' . $query . ' - user may not be authenticated or no data matched');
            } else {
                error_log('Successfully fetched context data for query: ' . $query . ' - Keys: ' . implode(', ', array_keys($contextData)));
            }
        } catch (Exception $dataException) {
            error_log('Error fetching data for analysis: ' . $dataException->getMessage() . ' in ' . $dataException->getFile() . ' on line ' . $dataException->getLine());
            // Continue without data - agent can still answer general questions
            $contextData = null;
        } catch (Error $dataError) {
            error_log('Fatal error fetching data: ' . $dataError->getMessage() . ' in ' . $dataError->getFile() . ' on line ' . $dataError->getLine());
            // Continue without data
            $contextData = null;
        }
    } else {
        error_log('Query does not require data: ' . $query);
    }

    // Load API key from config file or environment variable
    $geminiApiKey = '';

    // Try to load from config file first
    $configFile = __DIR__ . '/config.php';
    if (file_exists($configFile)) {
        try {
            $config = require $configFile;
            if (is_array($config) && isset($config['gemini_api_key'])) {
                $geminiApiKey = $config['gemini_api_key'];
            }
        } catch (Exception $e) {
            error_log('Error loading config file: ' . $e->getMessage());
        }
    }

    // Fallback to environment variable if config file doesn't have a valid key
    if (empty($geminiApiKey)) {
        $geminiApiKey = getenv('GEMINI_API_KEY') ?: '';
    }

    // If no API key is set, provide a mock response for testing
    if (empty($geminiApiKey) || $geminiApiKey === 'your-gemini-api-key-here') {
        // Mock response for testing
        $answer = "I'm a mock AI assistant. To enable real AI responses, please configure your Gemini API key in api/config.php. Your question was: " . htmlspecialchars($query);
    } else {
        // Call Gemini API with context data if available
        try {
            // Make sure the function is available
            if (!function_exists('callGemini')) {
                throw new Exception('callGemini function is not defined');
            }
            $answer = callGemini($geminiApiKey, $query, $contextData, $pageContext, $clientData);
        } catch (Exception $apiException) {
            // Log the API error
            error_log('Gemini API call failed: ' . $apiException->getMessage() . ' in ' . $apiException->getFile() . ' on line ' . $apiException->getLine());
            throw $apiException; // Re-throw to be caught by outer catch
        } catch (Error $apiError) {
            // Catch fatal errors from the API call
            error_log('Gemini API fatal error: ' . $apiError->getMessage() . ' in ' . $apiError->getFile() . ' on line ' . $apiError->getLine());
            throw new Exception('Fatal error in API call: ' . $apiError->getMessage());
        }
    }

    // Return response
    echo json_encode([
        'success' => true,
        'answer' => $answer
    ]);

} catch (Exception $e) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    error_log('Chat AI Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    error_log('Chat AI Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => 'A fatal error occurred: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}

/**
 * Detect if query requires data analysis
 */
function detectDataQuery($query)
{
    $dataKeywords = [
        'analyze',
        'analysis',
        'statistics',
        'stats',
        'summary',
        'overview',
        'dashboard',
        'report',
        'trend',
        'performance',
        'kpi',
        'metrics',
        'how many',
        'count',
        'list',
        'show me',
        'what are',
        'status of',
        'overdue',
        'pending',
        'active',
        'recent',
        'latest',
        'current',
        'details',
        'information',
        'about',
        'tell me',
        'explain',
        'describe',
        'view',
        'see',
        'display',
        'get',
        'fetch',
        'retrieve',
        'filter',
        'search',
        'find',
        'show',
        'containing',
        'with name',
        'named',
        'select',
        'where',
        'like' // SQL-like patterns for document queries
    ];

    // Check for entity ID patterns (e.g., "event #3", "permit 5", "task #10", "document #5")
    if (preg_match('/\b(?:event|incident|permit|task|risk|hazard|document|doc|sop|procedure|process)\s*#?\s*\d+\b/i', $query)) {
        return true;
    }

    // Check for "#" followed by number (likely an ID reference)
    if (preg_match('/#\s*\d+\b/i', $query)) {
        return true;
    }

    // Check for filter/search patterns (e.g., "filter permits with name X", "select documents where title like X")
    if (preg_match('/\b(?:filter|search|find|show|select)\s+\w+\s+(?:with|containing|named|called|where|like)/i', $query)) {
        return true;
    }

    // Check for SQL-like patterns: "select ... where ... like"
    if (preg_match('/\bselect\b.*\bwhere\b.*\blike\b/i', $query)) {
        return true;
    }

    $queryLower = strtolower($query);
    foreach ($dataKeywords as $keyword) {
        if (strpos($queryLower, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Fetch relevant data based on query - Direct database access
 */
function fetchRelevantData($query)
{
    // Check if user is authenticated
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    try {
        $database = new Database();
        $pdo = $database->getConnection();
    } catch (Exception $e) {
        error_log('Database connection error: ' . $e->getMessage());
        return null;
    }

    $data = [];

    try {
        // Dashboard stats (general overview)
        if (preg_match('/\b(dashboard|overview|summary|statistics|stats)\b/i', $query)) {
            $data['dashboard'] = getDashboardStats($pdo);
        }

        // Document-related queries (SOPs, procedures, processes)
        if (preg_match('/\b(document|documents|doc|docs|sop|sops|procedure|procedures|process|processes)\b/i', $query)) {
            // Check for specific document ID
            $documentId = null;
            if (preg_match('/\b(?:document|doc|sop|procedure|process)\s*#?\s*(\d+)\b/i', $query, $idMatches)) {
                $documentId = (int) $idMatches[1];
            } elseif (preg_match('/#\s*(\d+)\b/i', $query, $idMatches) && preg_match('/\b(document|doc|sop|procedure|process)\b/i', $query)) {
                $documentId = (int) $idMatches[1];
            } elseif (preg_match('/\b(?:document|doc|sop|procedure|process)\s+(?:number|id|no\.?)\s*#?\s*(\d+)\b/i', $query, $idMatches)) {
                $documentId = (int) $idMatches[1];
            }

            if ($documentId) {
                // Fetch specific document by ID
                $document = getDocumentById($pdo, $documentId);
                if ($document) {
                    $data['document'] = $document;
                } else {
                    $data['document'] = ['error' => "Document #{$documentId} not found"];
                }
            } else {
                // Fetch summary of documents with search/filter support
                $params = [];

                // Check for status filter
                if (preg_match('/\b(active|draft|obsolete|deleted|approved|pending)\b/i', $query, $matches)) {
                    $params['status'] = ucfirst(strtolower($matches[0]));
                }

                // Check for search/filter terms (e.g., "filter documents with name X", "documents containing X")
                $searchTerms = [];

                // Pattern 1: "select documents where title like X", "select documents where X like Y"
                if (preg_match('/\bselect\s+(?:documents?|docs?|sops?|procedures?|processes?)\s+where\s+(?:\w+\s+)?like\s+["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $searchTerms[] = trim($matches[1]);
                }
                // Pattern 2: "filter documents with name X", "documents with name X"
                elseif (preg_match('/\b(?:filter|search|find|show|select)\s+(?:documents?|docs?|sops?|procedures?|processes?)\s+(?:with|containing|having|named|called)\s+(?:name|title|code)?\s+["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $searchTerms[] = trim($matches[1]);
                }
                // Pattern 3: "documents with X", "documents containing X", "documents where X like Y"
                elseif (preg_match('/\b(?:documents?|docs?|sops?|procedures?|processes?)\s+(?:with|containing|having|named|called|for|where)\s+(?:\w+\s+)?(?:like\s+)?["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $searchTerms[] = trim($matches[1]);
                }
                // Pattern 4: Extract words after "filter", "search", "find", "select" that aren't common words
                elseif (preg_match('/\b(?:filter|search|find|show|select)\s+(?:documents?|docs?|sops?|procedures?|processes?)\s+(?:with|containing|where|like)?\s*["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $potentialTerm = trim($matches[1]);
                    // Remove common words
                    $potentialTerm = preg_replace('/\b(with|name|title|code|containing|having|named|called|for|the|a|an|where|like|select)\b/i', '', $potentialTerm);
                    $potentialTerm = trim($potentialTerm);
                    if (!empty($potentialTerm) && strlen($potentialTerm) > 2) {
                        $searchTerms[] = $potentialTerm;
                    }
                }

                // If we found search terms, add them to params
                if (!empty($searchTerms)) {
                    $params['search'] = implode(' ', $searchTerms);
                }

                $data['documents'] = getDocumentSummary($pdo, $params);
            }
        }

        // Risk-related queries
        if (preg_match('/\b(risk|risks|hazard|hazards)\b/i', $query)) {
            // Check for specific risk ID
            $riskId = null;
            if (preg_match('/\b(?:risk|hazard)\s*#?\s*(\d+)\b/i', $query, $idMatches)) {
                $riskId = (int) $idMatches[1];
            } elseif (preg_match('/#\s*(\d+)\b/i', $query, $idMatches) && preg_match('/\b(risk|hazard)\b/i', $query)) {
                $riskId = (int) $idMatches[1];
            } elseif (preg_match('/\b(?:risk|hazard)\s+(?:number|id|no\.?)\s*#?\s*(\d+)\b/i', $query, $idMatches)) {
                $riskId = (int) $idMatches[1];
            }

            if ($riskId) {
                // Fetch specific risk by ID
                $risk = getRiskById($pdo, $riskId);
                if ($risk) {
                    $data['risk'] = $risk;
                } else {
                    $data['risk'] = ['error' => "Risk #{$riskId} not found"];
                }
            } else {
                // Fetch summary of risks with search/filter support
                $params = [];
                if (preg_match('/\b(critical|high|medium|low)\b/i', $query, $matches)) {
                    $params['priority'] = ucfirst(strtolower($matches[0]));
                }
                if (preg_match('/\b(active|closed|draft)\b/i', $query, $matches)) {
                    $params['status'] = ucfirst(strtolower($matches[0]));
                }

                // Check for search/filter terms
                $searchTerms = [];
                if (preg_match('/\b(?:filter|search|find|show)\s+(?:risks?|hazards?)\s+(?:with|containing|having|named|called)\s+(?:name|title)?\s+["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $searchTerms[] = trim($matches[1]);
                } elseif (preg_match('/\b(?:risks?|hazards?)\s+(?:with|containing|having|named|called|for)\s+["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $searchTerms[] = trim($matches[1]);
                } elseif (preg_match('/\b(?:filter|search|find|show)\s+(?:risks?|hazards?)\s+(?:with|containing)?\s*["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $potentialTerm = trim($matches[1]);
                    $potentialTerm = preg_replace('/\b(with|name|title|containing|having|named|called|for|the|a|an)\b/i', '', $potentialTerm);
                    $potentialTerm = trim($potentialTerm);
                    if (!empty($potentialTerm) && strlen($potentialTerm) > 2) {
                        $searchTerms[] = $potentialTerm;
                    }
                }

                if (!empty($searchTerms)) {
                    $params['search'] = implode(' ', $searchTerms);
                }

                $data['risks'] = getRiskSummary($pdo, $params);
            }
        }

        // Permit-related queries
        if (preg_match('/\b(permit|permits|ptw)\b/i', $query)) {
            // Check for specific permit ID
            $permitId = null;
            if (preg_match('/\b(?:permit|ptw)\s*#?\s*(\d+)\b/i', $query, $idMatches)) {
                $permitId = (int) $idMatches[1];
            } elseif (preg_match('/#\s*(\d+)\b/i', $query, $idMatches) && preg_match('/\b(permit|ptw)\b/i', $query)) {
                $permitId = (int) $idMatches[1];
            } elseif (preg_match('/\b(?:permit|ptw)\s+(?:number|id|no\.?)\s*#?\s*(\d+)\b/i', $query, $idMatches)) {
                $permitId = (int) $idMatches[1];
            }

            if ($permitId) {
                // Fetch specific permit by ID
                $permit = getPermitById($pdo, $permitId);
                if ($permit) {
                    $data['permit'] = $permit;
                } else {
                    $data['permit'] = ['error' => "Permit #{$permitId} not found"];
                }
            } else {
                // Fetch summary of permits with search/filter support
                $params = [];

                // Check for status filter
                if (preg_match('/\b(active|expired|pending)\b/i', $query, $matches)) {
                    $params['status'] = ucfirst(strtolower($matches[0]));
                }

                // Check for search/filter terms (e.g., "filter permits with name olivier", "permits containing olivier")
                $searchTerms = [];

                // Pattern 1: "filter permits with name X", "permits with name X"
                if (preg_match('/\b(?:filter|search|find|show)\s+(?:permits?|ptw)\s+(?:with|containing|having|named|called)\s+(?:name|title|task)?\s+["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $searchTerms[] = trim($matches[1]);
                }
                // Pattern 2: "permits with X", "permits containing X"
                elseif (preg_match('/\b(?:permits?|ptw)\s+(?:with|containing|having|named|called|for)\s+["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $searchTerms[] = trim($matches[1]);
                }
                // Pattern 3: Extract words after "filter", "search", "find" that aren't common words
                elseif (preg_match('/\b(?:filter|search|find|show)\s+(?:permits?|ptw)\s+(?:with|containing)?\s*["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $potentialTerm = trim($matches[1]);
                    // Remove common words
                    $potentialTerm = preg_replace('/\b(with|name|title|task|containing|having|named|called|for|the|a|an)\b/i', '', $potentialTerm);
                    $potentialTerm = trim($potentialTerm);
                    if (!empty($potentialTerm) && strlen($potentialTerm) > 2) {
                        $searchTerms[] = $potentialTerm;
                    }
                }

                // If we found search terms, add them to params
                if (!empty($searchTerms)) {
                    $params['search'] = implode(' ', $searchTerms);
                }

                $data['permits'] = getPermitSummary($pdo, $params);
            }
        }

        // Incident/Event-related queries
        if (preg_match('/\b(incident|incidents|event|events|accident|near miss)\b/i', $query)) {
            // Check for specific event ID (e.g., "event #3", "event 3", "analyze event 3", "show me event #3")
            $eventId = null;
            // Pattern 1: "event #3", "incident 3", "event 3", etc.
            if (preg_match('/\b(?:event|incident|accident)\s*#?\s*(\d+)\b/i', $query, $idMatches)) {
                $eventId = (int) $idMatches[1];
            }
            // Pattern 2: "#3" or "# 3" when event/incident context is already established
            elseif (preg_match('/#\s*(\d+)\b/i', $query, $idMatches)) {
                $eventId = (int) $idMatches[1];
            }
            // Pattern 3: "event number 3", "event id 3", etc.
            elseif (preg_match('/\b(?:event|incident|accident)\s+(?:number|id|no\.?)\s*#?\s*(\d+)\b/i', $query, $idMatches)) {
                $eventId = (int) $idMatches[1];
            }

            if ($eventId) {
                // Fetch specific event by ID
                $event = getEventById($pdo, $eventId);
                if ($event) {
                    $data['event'] = $event; // Use singular 'event' for specific event
                } else {
                    $data['event'] = ['error' => "Event #{$eventId} not found"];
                }
            } else {
                // Fetch summary of events with search/filter support
                $params = [];
                if (preg_match('/\b(last|past|recent)\s+(\d+)\s*(day|days|week|weeks|month|months)\b/i', $query, $matches)) {
                    $num = (int) $matches[2];
                    $unit = strtolower($matches[3]);
                    $days = $unit === 'month' || $unit === 'months' ? $num * 30 : ($unit === 'week' || $unit === 'weeks' ? $num * 7 : $num);
                    $params['days'] = $days;
                }

                // Check for search/filter terms
                $searchTerms = [];
                if (preg_match('/\b(?:filter|search|find|show)\s+(?:events?|incidents?|accidents?)\s+(?:with|containing|having|named|called)\s+(?:name|description)?\s+["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $searchTerms[] = trim($matches[1]);
                } elseif (preg_match('/\b(?:events?|incidents?|accidents?)\s+(?:with|containing|having|named|called|for)\s+["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $searchTerms[] = trim($matches[1]);
                } elseif (preg_match('/\b(?:filter|search|find|show)\s+(?:events?|incidents?|accidents?)\s+(?:with|containing)?\s*["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $potentialTerm = trim($matches[1]);
                    $potentialTerm = preg_replace('/\b(with|name|description|containing|having|named|called|for|the|a|an)\b/i', '', $potentialTerm);
                    $potentialTerm = trim($potentialTerm);
                    if (!empty($potentialTerm) && strlen($potentialTerm) > 2) {
                        $searchTerms[] = $potentialTerm;
                    }
                }

                if (!empty($searchTerms)) {
                    $params['search'] = implode(' ', $searchTerms);
                }

                $data['incidents'] = getIncidentSummary($pdo, $params);
            }
        }

        // KPI queries
        if (preg_match('/\b(kpi|key performance|metrics|performance)\b/i', $query)) {
            $params = [];
            if (preg_match('/\b(last|past|recent)\s+(\d+)\s*(day|days|week|weeks|month|months)\b/i', $query, $matches)) {
                $num = (int) $matches[2];
                $unit = strtolower($matches[3]);
                $days = $unit === 'month' || $unit === 'months' ? $num * 30 : ($unit === 'week' || $unit === 'weeks' ? $num * 7 : $num);
                $params['days'] = $days;
            }
            $data['kpis'] = getKPIData($pdo, $params);
        }

        // Overdue items
        if (preg_match('/\b(overdue|past due|expired|late)\b/i', $query)) {
            $data['overdue'] = getOverdueItems($pdo);
        }

        // Task-related queries
        if (preg_match('/\b(task|tasks)\b/i', $query)) {
            // Check for specific task ID
            $taskId = null;
            if (preg_match('/\btask\s*#?\s*(\d+)\b/i', $query, $idMatches)) {
                $taskId = (int) $idMatches[1];
            } elseif (preg_match('/#\s*(\d+)\b/i', $query, $idMatches) && preg_match('/\btask\b/i', $query)) {
                $taskId = (int) $idMatches[1];
            } elseif (preg_match('/\btask\s+(?:number|id|no\.?)\s*#?\s*(\d+)\b/i', $query, $idMatches)) {
                $taskId = (int) $idMatches[1];
            }

            if ($taskId) {
                // Fetch specific task by ID
                $task = getTaskById($pdo, $taskId);
                if ($task) {
                    $data['task'] = $task;
                } else {
                    $data['task'] = ['error' => "Task #{$taskId} not found"];
                }
            } else {
                // Fetch summary of tasks with search/filter support
                $params = [];

                // Check for status filter
                if (preg_match('/\b(pending|in progress|completed)\b/i', $query, $matches)) {
                    $params['status'] = ucwords(strtolower($matches[0]));
                }

                // Check for search/filter terms
                $searchTerms = [];
                if (preg_match('/\b(?:filter|search|find|show)\s+(?:tasks?)\s+(?:with|containing|having|named|called)\s+(?:name|title)?\s+["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $searchTerms[] = trim($matches[1]);
                } elseif (preg_match('/\b(?:tasks?)\s+(?:with|containing|having|named|called|for)\s+["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $searchTerms[] = trim($matches[1]);
                } elseif (preg_match('/\b(?:filter|search|find|show)\s+(?:tasks?)\s+(?:with|containing)?\s*["\']?([^"\']+)["\']?/i', $query, $matches)) {
                    $potentialTerm = trim($matches[1]);
                    $potentialTerm = preg_replace('/\b(with|name|title|containing|having|named|called|for|the|a|an)\b/i', '', $potentialTerm);
                    $potentialTerm = trim($potentialTerm);
                    if (!empty($potentialTerm) && strlen($potentialTerm) > 2) {
                        $searchTerms[] = $potentialTerm;
                    }
                }

                if (!empty($searchTerms)) {
                    $params['search'] = implode(' ', $searchTerms);
                }

                $data['tasks'] = getTaskSummary($pdo, $params);
            }
        }

    } catch (Exception $e) {
        error_log('Error fetching data: ' . $e->getMessage());
    }

    return !empty($data) ? $data : null;
}

/**
 * Data fetching functions - Direct database access
 */
function getDashboardStats($pdo)
{
    $stats = [];

    // Risk statistics
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total, 
                            SUM(CASE WHEN priority = 'Critical' OR priority = 'Emergency' THEN 1 ELSE 0 END) as critical,
                            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active
                            FROM risk_register");
        $stats['risks'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stats['risks'] = ['error' => 'Unable to fetch risk data: ' . $e->getMessage()];
    }

    // Permit statistics
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total,
                            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
                            SUM(CASE WHEN status = 'Expired' THEN 1 ELSE 0 END) as expired,
                            SUM(CASE WHEN expiry_date < CURDATE() AND status = 'Active' THEN 1 ELSE 0 END) as overdue
                            FROM permits");
        $stats['permits'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stats['permits'] = ['error' => 'Unable to fetch permit data: ' . $e->getMessage()];
    }

    // Task statistics
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total,
                            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                            SUM(CASE WHEN due_date < CURDATE() AND status != 'Completed' THEN 1 ELSE 0 END) as overdue
                            FROM tasks");
        $stats['tasks'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stats['tasks'] = ['error' => 'Unable to fetch task data: ' . $e->getMessage()];
    }

    // Event/Incident statistics
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total,
                            SUM(CASE WHEN event_type = 'Incident' THEN 1 ELSE 0 END) as incidents,
                            SUM(CASE WHEN event_type = 'Near Miss' THEN 1 ELSE 0 END) as near_misses
                            FROM events
                            WHERE reported_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stats['events'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stats['events'] = ['error' => 'Unable to fetch event data: ' . $e->getMessage()];
    }

    return $stats;
}

function getRiskSummary($pdo, $params)
{
    $where = ['1=1'];
    $queryParams = [];

    if (!empty($params['status'])) {
        $where[] = 'r.status = :status';
        $queryParams[':status'] = $params['status'];
    }

    if (!empty($params['priority'])) {
        $where[] = 'r.priority = :priority';
        $queryParams[':priority'] = $params['priority'];
    }

    // Add search functionality
    if (!empty($params['search'])) {
        $searchTerm = trim($params['search']);
        $searchTerm = preg_replace('/\b(with|name|title|containing|having|named|called|for)\b/i', '', $searchTerm);
        $searchTerm = trim($searchTerm);

        if (!empty($searchTerm)) {
            // Search across risk_title, risk_description, and risk_code
            $where[] = '(r.risk_title LIKE :search OR 
                        r.risk_description LIKE :search OR
                        r.risk_code LIKE :search)';
            $queryParams[':search'] = '%' . $searchTerm . '%';
        }
    }

    $sql = "SELECT r.risk_id, r.risk_code, r.risk_title, r.risk_description, 
                   r.category_id, r.priority, r.status, r.date_identified, r.risk_owner
            FROM risk_register r
            WHERE " . implode(' AND ', $where) . "
            ORDER BY r.date_identified DESC
            LIMIT 50";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($queryParams);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching risk summary: ' . $e->getMessage());
        return [];
    }
}

function getPermitSummary($pdo, $params)
{
    $where = ['1=1'];
    $queryParams = [];

    if (!empty($params['status'])) {
        $where[] = 'p.status = :status';
        $queryParams[':status'] = $params['status'];
    }

    if (!empty($params['type'])) {
        $where[] = 'p.permit_type = :type';
        $queryParams[':type'] = $params['type'];
    }

    // Add search functionality - search across multiple fields
    if (!empty($params['search'])) {
        $searchTerm = trim($params['search']);
        // Remove common filter words if they're part of the search term
        $searchTerm = preg_replace('/\b(with|name|title|task|containing|having|named|called|for)\b/i', '', $searchTerm);
        $searchTerm = trim($searchTerm);

        if (!empty($searchTerm)) {
            // Search across task_name, issued_by_name, permit_type, and conditions
            $where[] = '(t.task_name LIKE :search OR 
                        CONCAT(issuer.FirstName, " ", issuer.LastName) LIKE :search OR 
                        p.permit_type LIKE :search OR
                        p.conditions LIKE :search)';
            $queryParams[':search'] = '%' . $searchTerm . '%';
        }
    }

    $sql = "SELECT p.permit_id, p.permit_type, p.status, p.issue_date, p.expiry_date,
                   t.task_name, 
                   CONCAT(issuer.FirstName, ' ', issuer.LastName) AS issued_by_name
            FROM permits p
            LEFT JOIN tasks t ON p.task_id = t.task_id
            LEFT JOIN people issuer ON p.issued_by = issuer.people_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.issue_date DESC
            LIMIT 50";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($queryParams);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching permit summary: ' . $e->getMessage());
        return [];
    }
}

function getEventById($pdo, $eventId)
{
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.event_id,
                e.event_type,
                e.reported_by,
                e.reported_date,
                e.description,
                e.status,
                e.event_subcategory,
                e.likelihood,
                e.severity,
                e.risk_rating,
                e.department_id,
                CONCAT(p.FirstName, ' ', p.LastName) AS reported_by_name,
                d.DepartmentName
            FROM events e
            LEFT JOIN people p ON e.reported_by = p.people_id
            LEFT JOIN departments d ON e.department_id = d.department_id
            WHERE e.event_id = :event_id
        ");
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching event by ID: ' . $e->getMessage());
        return null;
    }
}

function getPermitById($pdo, $permitId)
{
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.permit_id,
                p.task_id,
                p.permit_type,
                p.issued_by,
                CONCAT(ib.FirstName, ' ', ib.LastName) AS issued_by_name,
                p.approved_by,
                CONCAT(ab.FirstName, ' ', ab.LastName) AS approved_by_name,
                p.issue_date,
                p.expiry_date,
                p.status AS permit_status,
                p.conditions,
                p.Dep_owner,
                CONCAT(do_person.FirstName, ' ', do_person.LastName) AS dep_owner_name,
                t.task_name,
                t.task_description,
                t.task_type,
                t.start_date,
                t.finish_date,
                t.due_date,
                t.priority,
                t.status AS task_status
            FROM permits p
            LEFT JOIN tasks t ON p.task_id = t.task_id
            LEFT JOIN people ib ON p.issued_by = ib.people_id
            LEFT JOIN people ab ON p.approved_by = ab.people_id
            LEFT JOIN people do_person ON p.Dep_owner = do_person.people_id
            WHERE p.permit_id = :permit_id
        ");
        $stmt->execute([':permit_id' => $permitId]);
        $permit = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($permit) {
            // Fetch associated steps
            $stepsStmt = $pdo->prepare("
                SELECT step_id, step_number, step_description, hazard_description, control_description
                FROM permit_steps
                WHERE permit_id = :permit_id
                ORDER BY step_number
            ");
            $stepsStmt->execute([':permit_id' => $permitId]);
            $permit['steps'] = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $permit;
    } catch (PDOException $e) {
        error_log('Error fetching permit by ID: ' . $e->getMessage());
        return null;
    }
}

function getTaskById($pdo, $taskId)
{
    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.task_id,
                t.task_name,
                t.task_description,
                t.task_type,
                t.start_date,
                t.finish_date,
                t.due_date,
                t.priority,
                t.status,
                t.created_date,
                t.updated_date,
                t.department_id,
                d.DepartmentName,
                t.assigned_to,
                CONCAT(p.FirstName, ' ', p.LastName) AS assigned_to_name
            FROM tasks t
            LEFT JOIN departments d ON t.department_id = d.department_id
            LEFT JOIN people p ON t.assigned_to = p.people_id
            WHERE t.task_id = :task_id
        ");
        $stmt->execute([':task_id' => $taskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching task by ID: ' . $e->getMessage());
        return null;
    }
}

function getRiskById($pdo, $riskId)
{
    try {
        $stmt = $pdo->prepare("
            SELECT 
                r.risk_id,
                r.risk_code,
                r.risk_title,
                r.risk_description,
                r.category_id,
                r.priority,
                r.status,
                r.date_identified,
                r.risk_owner,
                r.likelihood,
                r.impact,
                r.risk_level,
                r.control_measures,
                r.residual_risk,
                r.next_review_date
            FROM risk_register r
            WHERE r.risk_id = :risk_id
        ");
        $stmt->execute([':risk_id' => $riskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching risk by ID: ' . $e->getMessage());
        return null;
    }
}

function getDocumentById($pdo, $documentId)
{
    try {
        // Check if BaseDocumentID column exists
        $hasBaseDocumentId = false;
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM documents LIKE 'BaseDocumentID'");
            $hasBaseDocumentId = $colStmt->fetch() !== false;
        } catch (Exception $e) {
            $hasBaseDocumentId = false;
        }

        if ($hasBaseDocumentId) {
            $sql = "SELECT d.DocumentID, d.DocCode, d.Title, d.Description, 
                           d.EffectiveDate, d.OwnerUserID, d.UploadedBy, d.StatusID,
                           d.CurrentVersionID, d.VersionNumber, d.RevisionLabel,
                           s.StatusName, dt.Name AS DocumentType
                    FROM documents d
                    LEFT JOIN documentstatuses s ON d.StatusID = s.StatusID
                    LEFT JOIN document_types dt ON d.DocumentTypeID = dt.DocumentTypeID
                    WHERE d.DocumentID = :document_id AND d.BaseDocumentID IS NULL";
        } else {
            $sql = "SELECT d.DocumentID, d.DocCode, d.Title, d.Description, 
                           d.EffectiveDate, d.OwnerUserID, d.UploadedBy, d.StatusID,
                           d.CurrentVersionID,
                           s.StatusName, dt.Name AS DocumentType, 
                           dv.VersionNumber, dv.RevisionLabel
                    FROM documents d
                    LEFT JOIN documentstatuses s ON d.StatusID = s.StatusID
                    LEFT JOIN document_types dt ON d.DocumentTypeID = dt.DocumentTypeID
                    LEFT JOIN documentversions dv ON d.CurrentVersionID = dv.VersionID
                    WHERE d.DocumentID = :document_id";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':document_id' => $documentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching document by ID: ' . $e->getMessage());
        return null;
    }
}

function getDocumentSummary($pdo, $params)
{
    try {
        // Check if BaseDocumentID column exists
        $hasBaseDocumentId = false;
        try {
            $colStmt = $pdo->query("SHOW COLUMNS FROM documents LIKE 'BaseDocumentID'");
            $hasBaseDocumentId = $colStmt->fetch() !== false;
        } catch (Exception $e) {
            $hasBaseDocumentId = false;
        }

        $where = [];
        $queryParams = [];

        // Base condition for document structure
        if ($hasBaseDocumentId) {
            $where[] = 'd.BaseDocumentID IS NULL';
        }

        // Exclude deleted/obsolete by default
        $where[] = '(s.StatusName NOT IN (\'Obsolete\', \'Deleted\') OR s.StatusName IS NULL)';

        // Status filter
        if (!empty($params['status'])) {
            $where[] = 's.StatusName = :status';
            $queryParams[':status'] = ucfirst(strtolower($params['status']));
        }

        // Search functionality - search across ALL text fields
        if (!empty($params['search'])) {
            $searchTerm = trim($params['search']);
            // Remove common filter words if they're part of the search term
            $searchTerm = preg_replace('/\b(with|name|title|code|containing|having|named|called|for|where|like|select)\b/i', '', $searchTerm);
            $searchTerm = trim($searchTerm);

            if (!empty($searchTerm)) {
                // Search across ALL text fields: Title, Description, DocCode, DocumentType, StatusName, VersionNumber, RevisionLabel
                $where[] = '(d.Title LIKE :search OR 
                            d.Description LIKE :search OR 
                            d.DocCode LIKE :search OR
                            dt.Name LIKE :search OR
                            s.StatusName LIKE :search OR
                            COALESCE(d.VersionNumber, dv.VersionNumber, \'\') LIKE :search OR
                            COALESCE(d.RevisionLabel, dv.RevisionLabel, \'\') LIKE :search)';
                $queryParams[':search'] = '%' . $searchTerm . '%';
            }
        }

        if ($hasBaseDocumentId) {
            $sql = "SELECT d.DocumentID, d.DocCode, d.Title, d.Description, 
                           s.StatusName, dt.Name AS DocumentType, 
                           d.CurrentVersionID, d.VersionNumber, d.RevisionLabel, 
                           d.EffectiveDate, d.StatusID
                    FROM documents d
                    LEFT JOIN documentstatuses s ON d.StatusID = s.StatusID
                    LEFT JOIN document_types dt ON d.DocumentTypeID = dt.DocumentTypeID
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY d.DocCode, d.Title
                    LIMIT 50";
        } else {
            $sql = "SELECT d.DocumentID, d.DocCode, d.Title, d.Description, 
                           s.StatusName, dt.Name AS DocumentType, 
                           d.CurrentVersionID,
                           dv.VersionNumber, dv.RevisionLabel, d.EffectiveDate, d.StatusID
                    FROM documents d
                    LEFT JOIN documentstatuses s ON d.StatusID = s.StatusID
                    LEFT JOIN document_types dt ON d.DocumentTypeID = dt.DocumentTypeID
                    LEFT JOIN documentversions dv ON d.CurrentVersionID = dv.VersionID
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY d.DocCode, d.Title
                    LIMIT 50";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($queryParams);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching document summary: ' . $e->getMessage());
        return [];
    }
}

function getIncidentSummary($pdo, $params)
{
    $where = ['1=1'];
    $queryParams = [];

    if (!empty($params['type'])) {
        $where[] = 'e.event_type = :type';
        $queryParams[':type'] = $params['type'];
    }

    $days = $params['days'] ?? 30;
    $where[] = 'e.reported_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)';
    $queryParams[':days'] = $days;

    // Add search functionality
    if (!empty($params['search'])) {
        $searchTerm = trim($params['search']);
        $searchTerm = preg_replace('/\b(with|name|description|containing|having|named|called|for)\b/i', '', $searchTerm);
        $searchTerm = trim($searchTerm);

        if (!empty($searchTerm)) {
            // Search across description, event_type, and reported_by_name
            $where[] = '(e.description LIKE :search OR 
                        e.event_type LIKE :search OR
                        CONCAT(p.FirstName, " ", p.LastName) LIKE :search)';
            $queryParams[':search'] = '%' . $searchTerm . '%';
        }
    }

    $sql = "SELECT e.event_id, e.event_type, e.description, e.status, e.reported_date, e.severity,
                   CONCAT(p.FirstName, ' ', p.LastName) AS reported_by_name
            FROM events e
            LEFT JOIN people p ON e.reported_by = p.people_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY e.reported_date DESC
            LIMIT 50";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($queryParams);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching incident summary: ' . $e->getMessage());
        return [];
    }
}

function getKPIData($pdo, $params)
{
    $days = $params['days'] ?? 30;

    $kpis = [];

    // Safety incidents
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, event_type
                              FROM events
                              WHERE reported_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                              GROUP BY event_type");
        $stmt->execute([':days' => $days]);
        $kpis['incidents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $kpis['incidents'] = [];
    }

    // Permit compliance
    try {
        $stmt = $pdo->query("SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN expiry_date < CURDATE() AND status = 'Active' THEN 1 ELSE 0 END) as expired,
                            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active
                            FROM permits");
        $kpis['permits'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $kpis['permits'] = [];
    }

    // Risk distribution
    try {
        $stmt = $pdo->query("SELECT priority, COUNT(*) as count
                            FROM risk_register
                            WHERE status = 'Active'
                            GROUP BY priority");
        $kpis['risks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $kpis['risks'] = [];
    }

    return $kpis;
}

function getTaskSummary($pdo, $params)
{
    $where = ['1=1'];
    $queryParams = [];

    if (!empty($params['status'])) {
        $where[] = 't.status = :status';
        $queryParams[':status'] = $params['status'];
    }

    // Add search functionality
    if (!empty($params['search'])) {
        $searchTerm = trim($params['search']);
        $searchTerm = preg_replace('/\b(with|name|title|containing|having|named|called|for)\b/i', '', $searchTerm);
        $searchTerm = trim($searchTerm);

        if (!empty($searchTerm)) {
            // Search across task_name, task_description, and assigned_to_name
            $where[] = '(t.task_name LIKE :search OR 
                        t.task_description LIKE :search OR
                        CONCAT(p.FirstName, " ", p.LastName) LIKE :search)';
            $queryParams[':search'] = '%' . $searchTerm . '%';
        }
    }

    $sql = "SELECT t.task_id, t.task_name, t.task_description, t.status, t.priority, t.due_date,
                   CONCAT(p.FirstName, ' ', p.LastName) AS assigned_to_name
            FROM tasks t
            LEFT JOIN people p ON t.assigned_to = p.people_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY t.due_date ASC
            LIMIT 50";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($queryParams);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching task summary: ' . $e->getMessage());
        return [];
    }
}

function getOverdueItems($pdo)
{
    $overdue = [];

    // Overdue permits
    try {
        $stmt = $pdo->query("SELECT permit_id, permit_type, expiry_date, status
                            FROM permits
                            WHERE expiry_date < CURDATE() AND status = 'Active'
                            ORDER BY expiry_date ASC
                            LIMIT 10");
        $overdue['permits'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $overdue['permits'] = [];
    }

    // Overdue tasks
    try {
        $stmt = $pdo->query("SELECT task_id, task_name, due_date, status, priority
                            FROM tasks
                            WHERE due_date < CURDATE() AND status != 'Completed'
                            ORDER BY due_date ASC
                            LIMIT 10");
        $overdue['tasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $overdue['tasks'] = [];
    }

    // Risks due for review
    try {
        $stmt = $pdo->query("SELECT risk_id, risk_code, risk_title, next_review_date, priority
                            FROM risk_register
                            WHERE next_review_date < CURDATE() AND status = 'Active'
                            ORDER BY next_review_date ASC
                            LIMIT 10");
        $overdue['risks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $overdue['risks'] = [];
    }

    return $overdue;
}

/**
 * Call Google Gemini API
 */
function callGemini($apiKey, $query, $contextData = null, $pageContext = '', $clientData = '')
{
    // Check if cURL is available
    if (!function_exists('curl_init')) {
        throw new Exception('cURL extension is not enabled. Please enable it in your PHP configuration.');
    }

    // First, try to list available models to see what we have access to
    $listUrl = "https://generativelanguage.googleapis.com/v1/models?key=" . urlencode($apiKey);
    $ch = curl_init($listUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $modelsResponse = curl_exec($ch);
    $modelsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $availableModels = [];
    if ($modelsHttpCode === 200) {
        $modelsData = json_decode($modelsResponse, true);
        if (isset($modelsData['models']) && is_array($modelsData['models'])) {
            foreach ($modelsData['models'] as $model) {
                if (isset($model['name'])) {
                    // Check if model supports generateContent
                    $supportsGenerateContent = false;
                    if (isset($model['supportedGenerationMethods']) && is_array($model['supportedGenerationMethods'])) {
                        $supportsGenerateContent = in_array('generateContent', $model['supportedGenerationMethods']);
                    } else {
                        // If supportedGenerationMethods not available, assume it supports generateContent
                        $supportsGenerateContent = true;
                    }

                    if ($supportsGenerateContent) {
                        // Extract model name (format: models/gemini-pro or just gemini-pro)
                        $modelName = str_replace('models/', '', $model['name']);
                        $availableModels[] = $modelName;
                    }
                }
            }
        }
    }

    // Log available models for debugging
    if (!empty($availableModels)) {
        error_log('Available Gemini models: ' . implode(', ', $availableModels));
    }

    // Build the prompt with system instruction
    $systemPrompt = "You are a helpful EHS (Environment, Health, and Safety) assistant. Answer questions about permits, incidents, risk assessments, change control, and safety procedures.

    === UI CONTROL INSTRUCTIONS ===
    You can control the user interface by including specific tags in your response. USE THESE OFTEN to make the interface helpful:
    
    1. [ACTION:highlight:SELECTOR] 
       - Causes the UI element matching the CSS selector to flash/highlight.
       - Use this when explaining where to find something.
       - Example: \"You can find the save button here: [ACTION:highlight:#save-btn]\"
       - Example: \"The risk rating is displayed in this column: [ACTION:highlight:.risk-rating-col]\"
    
    2. [ACTION:view:ENTITY:ID]
       - Opens a specific entity.
       - Example: \"Here is permit #123: [ACTION:view:permit:123]\"
    
    3. [ACTION:create:ENTITY]
       - Opens the creation modal for an entity.
       - Example: \"I can help you create a new permit: [ACTION:create:permit]\"
    
    4. [ACTION:edit:ENTITY:ID]
       - Opens the edit modal.
       - Example: \"You can edit this document: [ACTION:edit:document:45]\"

    === CONTEXT AWARENESS ===
    - Use the 'Page Context' to understand where the user is (e.g., 'Permit List', 'Dashboard').
    - Use 'Client Data' to see what the user is seeing (tables, lists, forms).
    ";

    // Add context data if available
    $contextSection = '';
    if ($contextData) {
        // Log that we have context data for debugging
        error_log('Context data available: ' . json_encode(array_keys($contextData)));

        $contextSection = "\n\n=== IMPORTANT: SYSTEM DATA PROVIDED ===\n" .
            "The following data has been RETRIEVED FROM THE DATABASE to answer the user's question. " .
            "YOU MUST USE THIS DATA to provide your answer. DO NOT ask the user for this information - it is already provided below.\n\n" .
            "SYSTEM DATA:\n" .
            json_encode($contextData, JSON_PRETTY_PRINT) .
            "\n\n=== INSTRUCTIONS ===\n" .
            "1. USE THE DATA PROVIDED ABOVE to answer the question.\n" .
            "2. DO NOT ask the user for information that is already in the data above.\n" .
            "3. If the data contains a specific entity (event, permit, task, etc.), analyze THAT specific entity using the provided data.\n" .
            "4. Provide specific details, numbers, and insights from the actual data.\n" .
            "5. If analyzing a specific event/incident, use ALL the fields provided (event_type, description, status, severity, risk_rating, etc.).\n" .
            "6. If the data shows an error (like 'not found'), inform the user that the requested item was not found in the system.\n" .
            "7. Be thorough and use ALL relevant information from the provided data.\n\n";
    } else {
        error_log('No context data available for query: ' . $query);
    }

    $fullPrompt = $systemPrompt .
        ($pageContext ? "\n\n=== PAGE CONTEXT (Where the user is) ===\n" . $pageContext : "") .
        ($clientData ? "\n\n=== CLIENT DATA (What the user sees) ===\n" . $clientData : "") .
        $contextSection .
        "\n\nUser question: " . $query;

    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $fullPrompt
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 1024,
            'topP' => 0.8,
            'topK' => 40
        ]
    ];

    // Try different models and API versions
    // If we got available models, use those first
    $endpoints = [];

    if (!empty($availableModels)) {
        // Use available models first
        foreach ($availableModels as $model) {
            $endpoints[] = ['version' => 'v1', 'model' => $model];
        }
    }

    // Fallback to common model names
    $endpoints = array_merge($endpoints, [
        ['version' => 'v1', 'model' => 'gemini-pro'],
        ['version' => 'v1', 'model' => 'gemini-1.5-flash'],
        ['version' => 'v1', 'model' => 'gemini-1.5-pro'],
        ['version' => 'v1beta', 'model' => 'gemini-pro'],
        ['version' => 'v1beta', 'model' => 'gemini-1.5-flash'],
    ]);

    $lastError = null;

    foreach ($endpoints as $endpoint) {
        // Use the correct URL format for Gemini API
        $url = "https://generativelanguage.googleapis.com/{$endpoint['version']}/models/{$endpoint['model']}:generateContent?key=" . urlencode($apiKey);

        error_log("Trying endpoint: {$endpoint['version']}/models/{$endpoint['model']}");

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $lastError = 'Gemini API request failed: ' . $error;
            continue; // Try next endpoint
        }

        if ($httpCode === 200) {
            // Success! Parse and return the response
            $responseData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $lastError = 'Failed to parse Gemini API response: ' . json_last_error_msg();
                continue;
            }

            if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $lastError = 'Invalid response from Gemini API - missing text in response';
                continue;
            }

            // Success!
            return $responseData['candidates'][0]['content']['parts'][0]['text'];
        } else {
            // Not 200, but might be a different error - try next endpoint
            $errorData = json_decode($response, true);
            if (is_array($errorData) && isset($errorData['error']['message'])) {
                // If it's a NOT_FOUND error, try next endpoint
                if (strpos($errorData['error']['message'], 'NOT_FOUND') !== false || $httpCode === 404) {
                    $lastError = "Model {$endpoint['model']} not found in {$endpoint['version']}";
                    continue;
                }
            }
            // For other errors, we might want to stop, but let's try all endpoints first
            $lastError = "HTTP $httpCode: " . ($errorData['error']['message'] ?? 'Unknown error');
        }
    }

    // If we get here, all endpoints failed
    throw new Exception('All Gemini API endpoints failed. Last error: ' . $lastError);

}


