<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Stack Development MCQ Quiz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #10b981;
            --secondary-color: #f8fafc;
            --text-color: #334155;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --success-color: #22c55e;
            --error-color: #ef4444;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f1f5f9;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-color);
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        header p {
            color: #64748b;
        }
        
        .quiz-info {
            display: flex;
            justify-content: space-between;
            background-color: var(--secondary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .quiz-info div {
            display: flex;
            align-items: center;
        }
        
        .quiz-info i {
            margin-right: 8px;
            color: var(--primary-color);
        }
        
        .question-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }
        
        .question {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        
        .options {
            list-style-type: none;
        }
        
        .option {
            padding: 12px 15px;
            margin-bottom: 10px;
            background-color: var(--secondary-color);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .option:hover {
            background-color: #e2e8f0;
        }
        
        .option.selected {
            background-color: #cbd5e1;
        }
        
        .option.correct {
            background-color: #dcfce7;
            border-left: 4px solid var(--success-color);
        }
        
        .option.incorrect {
            background-color: #fee2e2;
            border-left: 4px solid var(--error-color);
        }
        
        .controls {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        button {
            padding: 12px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        
        button:hover {
            background-color: #059669;
        }
        
        button:disabled {
            background-color: #94a3b8;
            cursor: not-allowed;
        }
        
        .results {
            text-align: center;
            padding: 20px;
            background-color: var(--secondary-color);
            border-radius: 8px;
            margin-top: 30px;
            display: none;
        }
        
        .results h2 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .score {
            font-size: 2rem;
            font-weight: bold;
            margin: 15px 0;
        }
        
        .result-actions {
            margin-top: 20px;
        }
        
        .feedback {
            font-size: 0.9rem;
            margin-top: 10px;
            font-style: italic;
            display: none;
        }
        
        code {
            background-color: #f1f5f9;
            padding: 2px 4px;
            border-radius: 4px;
            font-family: 'Consolas', 'Monaco', monospace;
            color: #059669;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 20px 10px;
            }
            
            .quiz-info {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Full Stack Development Quiz</h1>
            <p>Test your knowledge with these 20 multiple choice questions</p>
        </header>
        
        <div class="quiz-info">
            <div>
                <i class="fas fa-question-circle"></i>
                <span>20 Questions</span>
            </div>
            <div>
                <i class="fas fa-clock"></i>
                <span>Time: <span id="time">30:00</span></span>
            </div>
            <div>
                <i class="fas fa-trophy"></i>
                <span>Pass mark: 70%</span>
            </div>
        </div>
        
        <div id="quiz-container">
            <!-- Questions will be injected here from JavaScript -->
        </div>
        
        <div class="controls">
            <button id="prev-btn" disabled>Previous</button>
            <button id="next-btn">Next</button>
            <button id="submit-btn" style="display: none;">Submit Quiz</button>
        </div>
        
        <div class="results" id="results">
            <h2>Quiz Results</h2>
            <p>You've completed the Full Stack Development quiz!</p>
            <div class="score">
                <span id="score">0</span>/20
            </div>
            <div id="percentage">0%</div>
            <div id="feedback" class="feedback"></div>
            <div class="result-actions">
                <button id="review-btn">Review Answers</button>
                <button id="restart-btn">Retake Quiz</button>
                <button id="restart-btn" onclick="window.location.href='../../MODELS/quizes.html'">Back to Quizzes</button>
                <button id="restart-btn" onclick="window.location.href='../../MODELS/index.html'">Back to Dashboard</button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const questions = [
                {
                    question: "Which of the following is NOT a common frontend framework?",
                    options: ["React", "Angular", "Django", "Vue.js"],
                    correct: 2
                },
                {
                    question: "What is the purpose of Node.js in full stack development?",
                    options: [
                        "Frontend UI rendering",
                        "Backend server-side JavaScript runtime",
                        "Database management",
                        "CSS styling"
                    ],
                    correct: 1
                },
                {
                    question: "Which database is commonly used with the MERN stack?",
                    options: ["MySQL", "PostgreSQL", "MongoDB", "SQLite"],
                    correct: 2
                },
                {
                    question: "What does REST stand for in RESTful API?",
                    options: [
                        "Representational State Transfer",
                        "Remote State Transfer",
                        "Representative State Transfer",
                        "Remote State Technology"
                    ],
                    correct: 0
                },
                {
                    question: "Which of the following is a NoSQL database?",
                    options: ["MySQL", "PostgreSQL", "MongoDB", "Oracle"],
                    correct: 2
                },
                {
                    question: "What is the purpose of Express.js?",
                    options: [
                        "Frontend framework",
                        "Backend web application framework",
                        "Database management system",
                        "CSS preprocessor"
                    ],
                    correct: 1
                },
                {
                    question: "Which HTTP method is used to create new resources in REST?",
                    options: ["GET", "POST", "PUT", "DELETE"],
                    correct: 1
                },
                {
                    question: "What is the purpose of middleware in Express.js?",
                    options: [
                        "Frontend routing",
                        "Database queries",
                        "Request processing functions",
                        "CSS styling"
                    ],
                    correct: 2
                },
                {
                    question: "Which of the following is a frontend state management library?",
                    options: ["Redux", "Express", "MongoDB", "Node.js"],
                    correct: 0
                },
                {
                    question: "What is the purpose of JWT in web development?",
                    options: [
                        "Database encryption",
                        "User authentication",
                        "CSS styling",
                        "Frontend routing"
                    ],
                    correct: 1
                },
                {
                    question: "Which of the following is a CSS preprocessor?",
                    options: ["SASS", "React", "Node.js", "Express"],
                    correct: 0
                },
                {
                    question: "What is the purpose of WebSocket?",
                    options: [
                        "Database connection",
                        "Real-time bidirectional communication",
                        "CSS styling",
                        "Static file serving"
                    ],
                    correct: 1
                },
                {
                    question: "Which of the following is a version control system?",
                    options: ["Git", "Node.js", "MongoDB", "Express"],
                    correct: 0
                },
                {
                    question: "What is the purpose of Docker in full stack development?",
                    options: [
                        "Frontend framework",
                        "Containerization platform",
                        "Database management",
                        "CSS styling"
                    ],
                    correct: 1
                },
                {
                    question: "Which of the following is a testing framework for JavaScript?",
                    options: ["Jest", "Express", "MongoDB", "Node.js"],
                    correct: 0
                },
                {
                    question: "What is the purpose of GraphQL?",
                    options: [
                        "Database management",
                        "Query language for APIs",
                        "CSS styling",
                        "Frontend framework"
                    ],
                    correct: 1
                },
                {
                    question: "Which of the following is a package manager for JavaScript?",
                    options: ["npm", "pip", "composer", "maven"],
                    correct: 0
                },
                {
                    question: "What is the purpose of TypeScript?",
                    options: [
                        "Database management",
                        "Static typing for JavaScript",
                        "CSS styling",
                        "Backend framework"
                    ],
                    correct: 1
                },
                {
                    question: "Which of the following is a CSS framework?",
                    options: ["Bootstrap", "React", "Node.js", "Express"],
                    correct: 0
                },
                {
                    question: "What is the purpose of CI/CD in software development?",
                    options: [
                        "Database management",
                        "Continuous Integration/Continuous Deployment",
                        "CSS styling",
                        "Frontend framework"
                    ],
                    correct: 1
                }
            ];

            let currentQuestion = 0;
            let score = 0;
            let timeLeft = 1800; // 30 minutes in seconds
            let timer;

            const quizContainer = document.getElementById('quiz-container');
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const submitBtn = document.getElementById('submit-btn');
            const resultsDiv = document.getElementById('results');
            const scoreSpan = document.getElementById('score');
            const percentageDiv = document.getElementById('percentage');
            const feedbackDiv = document.getElementById('feedback');
            const reviewBtn = document.getElementById('review-btn');
            const restartBtn = document.getElementById('restart-btn');
            const timeSpan = document.getElementById('time');

            function startTimer() {
                timer = setInterval(() => {
                    timeLeft--;
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timeSpan.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    
                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        submitQuiz();
                    }
                }, 1000);
            }

            function displayQuestion() {
                const question = questions[currentQuestion];
                const questionHTML = `
                    <div class="question-container">
                        <div class="question">${currentQuestion + 1}. ${question.question}</div>
                        <ul class="options">
                            ${question.options.map((option, index) => `
                                <li class="option" data-index="${index}">
                                    ${option}
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                `;
                quizContainer.innerHTML = questionHTML;

                // Add click event to options
                document.querySelectorAll('.option').forEach(option => {
                    option.addEventListener('click', selectOption);
                });

                // Update button states
                prevBtn.disabled = currentQuestion === 0;
                nextBtn.style.display = currentQuestion === questions.length - 1 ? 'none' : 'block';
                submitBtn.style.display = currentQuestion === questions.length - 1 ? 'block' : 'none';
            }

            function selectOption(e) {
                const selectedOption = e.target;
                const options = document.querySelectorAll('.option');
                
                options.forEach(option => option.classList.remove('selected'));
                selectedOption.classList.add('selected');
            }

            function submitQuiz() {
                clearInterval(timer);
                const selectedOptions = document.querySelectorAll('.option.selected');
                score = 0;

                selectedOptions.forEach(option => {
                    const questionIndex = Math.floor(Array.from(option.parentElement.parentElement.parentElement).indexOf(option.parentElement) / 4);
                    const selectedAnswer = parseInt(option.dataset.index);
                    
                    if (selectedAnswer === questions[questionIndex].correct) {
                        score++;
                    }
                });

                const percentage = (score / questions.length) * 100;
                scoreSpan.textContent = score;
                percentageDiv.textContent = `${percentage.toFixed(1)}%`;

                if (percentage >= 70) {
                    feedbackDiv.textContent = "Congratulations! You've passed the quiz!";
                    feedbackDiv.style.color = var(--success-color);
                } else {
                    feedbackDiv.textContent = "Keep practicing! You need 70% to pass.";
                    feedbackDiv.style.color = var(--error-color);
                }

                feedbackDiv.style.display = 'block';
                resultsDiv.style.display = 'block';
                document.querySelector('.controls').style.display = 'none';
            }

            function reviewAnswers() {
                currentQuestion = 0;
                displayQuestion();
                resultsDiv.style.display = 'none';
                document.querySelector('.controls').style.display = 'flex';
            }

            function restartQuiz() {
                currentQuestion = 0;
                score = 0;
                timeLeft = 1800;
                startTimer();
                displayQuestion();
                resultsDiv.style.display = 'none';
                document.querySelector('.controls').style.display = 'flex';
            }

            // Event listeners
            prevBtn.addEventListener('click', () => {
                currentQuestion--;
                displayQuestion();
            });

            nextBtn.addEventListener('click', () => {
                currentQuestion++;
                displayQuestion();
            });

            submitBtn.addEventListener('click', submitQuiz);
            reviewBtn.addEventListener('click', reviewAnswers);
            restartBtn.addEventListener('click', restartQuiz);

            // Start the quiz
            startTimer();
            displayQuestion();
        });
    </script>
</body>
</html> 