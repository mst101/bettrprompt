#!/usr/bin/env node

/**
 * Debug Script for n8n Workflow Inspection
 *
 * This script helps you:
 * 1. Save webhook input data to workflow_X_input.json
 * 2. Save JavaScript code to workflow_X_prepare_prompt.js
 * 3. Send them to the debug endpoints
 * 4. View results at https://app.localhost/workflow_X
 *
 * Usage:
 *   node debug-workflow.js --workflow 1 --input input.json
 *   node debug-workflow.js --workflow 1 --javascript code.js
 *   node debug-workflow.js --workflow 1 --execute
 *   node debug-workflow.js --workflow 1 --show
 */

const fs = require('fs')
const path = require('path')
const https = require('https')

const args = process.argv.slice(2)
const options = {}

// Parse command line arguments
for (let i = 0; i < args.length; i++) {
  if (args[i].startsWith('--')) {
    const key = args[i].substring(2)
    const value = args[i + 1]
    if (value && !value.startsWith('--')) {
      options[key] = value
      i++
    } else {
      options[key] = true
    }
  }
}

const workflow = options.workflow || 1
const baseUrl = 'https://app.localhost'
const debugDir = path.join(__dirname, 'storage', 'app', 'debug')

// Ensure debug directory exists
if (!fs.existsSync(debugDir)) {
  fs.mkdirSync(debugDir, { recursive: true })
}

async function saveInput() {
  const inputFile = options.input
  if (!inputFile) {
    console.error('Please specify input file: --input path/to/file.json')
    process.exit(1)
  }

  if (!fs.existsSync(inputFile)) {
    console.error(`Input file not found: ${inputFile}`)
    process.exit(1)
  }

  const inputData = JSON.parse(fs.readFileSync(inputFile, 'utf8'))
  const destFile = path.join(debugDir, `workflow_${workflow}_input.json`)

  fs.writeFileSync(destFile, JSON.stringify(inputData, null, 2))
  console.log(`✓ Input saved to: ${destFile}`)
  console.log(`  Access at: ${baseUrl}/workflow_${workflow}`)
}

async function saveJavaScript() {
  const jsFile = options.javascript
  if (!jsFile) {
    console.error('Please specify JavaScript file: --javascript path/to/file.js')
    process.exit(1)
  }

  if (!fs.existsSync(jsFile)) {
    console.error(`JavaScript file not found: ${jsFile}`)
    process.exit(1)
  }

  const jsCode = fs.readFileSync(jsFile, 'utf8')
  const destFile = path.join(debugDir, `workflow_${workflow}_prepare_prompt.js`)

  fs.writeFileSync(destFile, jsCode)
  console.log(`✓ JavaScript saved to: ${destFile}`)
  console.log(`  Access at: ${baseUrl}/workflow_${workflow}`)
}

async function sendRequest(method, path, data = null) {
  return new Promise((resolve, reject) => {
    const url = new URL(`${baseUrl}${path}`)
    const options = {
      hostname: url.hostname,
      port: url.port,
      path: url.pathname + url.search,
      method: method,
      headers: {
        'Content-Type': 'application/json',
      },
      rejectUnauthorized: false, // Allow self-signed certificates
    }

    const req = https.request(options, (res) => {
      let body = ''
      res.on('data', (chunk) => (body += chunk))
      res.on('end', () => {
        try {
          const result = JSON.parse(body)
          resolve({ status: res.statusCode, data: result })
        } catch (e) {
          resolve({ status: res.statusCode, data: body })
        }
      })
    })

    req.on('error', reject)

    if (data) {
      req.write(JSON.stringify(data))
    }
    req.end()
  })
}

async function executeWorkflow() {
  try {
    const response = await sendRequest('POST', `/api/debug/workflow_${workflow}/execute`)

    if (response.status !== 200) {
      console.error(`✗ Execution failed (${response.status}):`)
      console.error(JSON.stringify(response.data, null, 2))
      process.exit(1)
    }

    if (!response.data.success) {
      console.error('✗ Execution failed:')
      console.error(response.data.error)
      process.exit(1)
    }

    // Save output
    const outputFile = path.join(debugDir, `workflow_${workflow}_output.json`)
    fs.writeFileSync(outputFile, JSON.stringify(response.data.output, null, 2))

    console.log('✓ Workflow executed successfully')
    console.log(`  Output saved to: ${outputFile}`)
    console.log(`  View at: ${baseUrl}/workflow_${workflow}`)

    // Display summary
    if (response.data.output.system) {
      console.log('\n--- System Prompt (first 200 chars) ---')
      console.log(response.data.output.system.substring(0, 200) + '...')
    }

    if (response.data.output.messages) {
      console.log('\n--- Messages ---')
      const messages = response.data.output.messages
      if (Array.isArray(messages)) {
        messages.forEach((msg, i) => {
          const role = typeof msg === 'object' ? msg.role : 'message'
          const content = typeof msg === 'object' ? msg.content : msg
          const preview = typeof content === 'string' ? content.substring(0, 100) : JSON.stringify(content).substring(0, 100)
          console.log(`  [${i}] ${role}: ${preview}${typeof content === 'string' && content.length > 100 ? '...' : ''}`)
        })
      }
    }
  } catch (error) {
    console.error('✗ Request failed:', error.message)
    process.exit(1)
  }
}

async function showInfo() {
  const inputFile = path.join(debugDir, `workflow_${workflow}_input.json`)
  const jsFile = path.join(debugDir, `workflow_${workflow}_prepare_prompt.js`)
  const outputFile = path.join(debugDir, `workflow_${workflow}_output.json`)

  console.log(`\nWorkflow ${workflow} Debug Status:`)
  console.log(`URL: ${baseUrl}/workflow_${workflow}`)
  console.log(`\nFiles:`)

  if (fs.existsSync(inputFile)) {
    const size = fs.statSync(inputFile).size
    console.log(`  ✓ Input: ${inputFile} (${size} bytes)`)
  } else {
    console.log(`  ✗ Input: ${inputFile} (not found)`)
  }

  if (fs.existsSync(jsFile)) {
    const size = fs.statSync(jsFile).size
    console.log(`  ✓ JavaScript: ${jsFile} (${size} bytes)`)
  } else {
    console.log(`  ✗ JavaScript: ${jsFile} (not found)`)
  }

  if (fs.existsSync(outputFile)) {
    const size = fs.statSync(outputFile).size
    console.log(`  ✓ Output: ${outputFile} (${size} bytes)`)
  } else {
    console.log(`  ✗ Output: ${outputFile} (not found)`)
  }
}

async function main() {
  if (!options.workflow) {
    console.log('Usage: node debug-workflow.js --workflow <number> [options]')
    console.log('\nOptions:')
    console.log('  --input <file>       Save webhook input from JSON file')
    console.log('  --javascript <file>  Save JavaScript code from file')
    console.log('  --execute            Execute the workflow (requires input + JavaScript)')
    console.log('  --show               Show current debug files')
    console.log('\nExamples:')
    console.log('  node debug-workflow.js --workflow 1 --input workflow_1_input.json')
    console.log('  node debug-workflow.js --workflow 1 --javascript workflow_1_prepare_prompt.js')
    console.log('  node debug-workflow.js --workflow 1 --execute')
    console.log('  node debug-workflow.js --workflow 1 --show')
    process.exit(0)
  }

  try {
    if (options.input) {
      await saveInput()
    } else if (options.javascript) {
      await saveJavaScript()
    } else if (options.execute) {
      await executeWorkflow()
    } else if (options.show) {
      await showInfo()
    } else {
      console.log(`Workflow ${workflow} debug files:`)
      await showInfo()
    }
  } catch (error) {
    console.error('Error:', error.message)
    process.exit(1)
  }
}

main()
