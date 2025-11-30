pipeline {
    agent any 
    stages {
        stage('1. Checkout Code') {
            steps {
                echo 'Pulling the latest code from GitHub...'
                checkout scm
            }
        }
        stage('2. Validate PHP Syntax') {
            steps {
                echo 'Checking all PHP files for syntax errors...'
                // This command finds every PHP file and runs the syntax check
                sh 'find . -name "*.php" -print0 | xargs -0 -n 1 php -l'
            }
        }
        stage('3. CI Successful') {
            steps {
                echo 'Code validated successfully! Ready for Manual Deployment.'
            }
        }
    }
    post {
        always {
            cleanWs()
        }
    }
}