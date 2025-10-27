I want to visit https://ggapps.co.uk/blog/ and all subsequent pages e.g. https://ggapps.co.uk/blog/page/2/

For each blog post, extract:
- Blog title
- Blog date
- Blog text
- Key learnings
- Entities e.g. people, websites, companies etc.

Then make an executive summary of all key findings and entities found across all the blog posts.

Note: Create separate MindStudio sub work flows where appropriate.

===

# Blog Analysis and Summarization Agent

A comprehensive agent that systematically analyzes blog content from https://ggapps.co.uk/blog/ and its subsequent pages. It extracts key information from each blog post, identifies important entities, and compiles an executive summary of all findings across the blog posts.

## Invocation

This agent is designed to be manually invoked. The user will trigger the agent by pressing a button, with minimal input required since the target blog URL is pre-defined.

## System Prompt Outline

The system prompt should define the agent's purpose as:
- A specialized blog analyzer that extracts structured information
- Capable of identifying key learnings from blog content
- Skilled at recognizing named entities (people, websites, companies)
- Proficient in aggregating findings across multiple blog posts
- Expert at creating concise yet comprehensive executive summaries

## Workflow Structure

This agent uses two separate workflows:
1. **Main Workflow**: Manages pagination, coordinates blog post processing, and generates the final summary
2. **Blog Post Analysis Workflow**: Extracts and analyzes information from individual blog posts

### Main Workflow Steps

1. **Start**: Initialize the workflow with minimal user input
2. **Initialize Variables**: Set up the main URL and tracking variables
3. **Process Blog Index**: Extract all blog post links from the current page
4. **Process Pagination**: Find and track next page links
5. **Process Each Blog Post**: Loop through blog posts using the sub-workflow
6. **Generate Executive Summary**: Create a comprehensive summary of all findings
7. **Display Results**: Present the findings and summary to the user
8. **End**: Terminate the workflow

### Blog Post Analysis Workflow Steps

1. **Start**: Accept the blog post URL as a launch variable
2. **Scrape Blog Post**: Extract the raw HTML of the blog post
3. **Extract Information**: Use AI to identify and extract the required information
4. **Analyze Content**: Identify key learnings and entities
5. **Return Results**: Structure the data and return to the main workflow
6. **End**: Terminate the sub-workflow and return to main workflow

## Variables

```typescript
interface MainWorkflowVariables {
  // Input/tracking variables
  baseUrl: string;                   // The base URL of the blog (e.g., "https://ggapps.co.uk/blog/")
  currentPageUrl: string;            // URL of the current page being processed (e.g., "https://ggapps.co.uk/blog/page/2/")
  hasNextPage: boolean;              // Flag indicating if there are more pages to process
  nextPageUrl: string;               // URL of the next page to process, if any
  
  // Collected data variables
  blogPostUrls: string[];            // Array of all blog post URLs found (e.g., ["https://ggapps.co.uk/blog/post-1", "https://ggapps.co.uk/blog/post-2"])
  currentPageHtml: string;           // Raw HTML of the current blog index page
  processedPosts: number;            // Counter for processed posts
  totalPosts: number;                // Total number of posts to process
  
  // Result storage
  blogData: BlogPostData[];          // Array of all processed blog post data
  executiveSummary: string;          // The final executive summary (multi-paragraph text)
  entitySummary: string;             // Summary of key entities found across all posts
}

interface BlogPostData {
  url: string;                       // URL of the blog post
  title: string;                     // Title of the blog post (e.g., "How to Improve App Performance")
  date: string;                      // Published date of the blog post (e.g., "January 15, 2023")
  text: string;                      // Main text content of the blog post
  keyLearnings: string[];            // Array of key takeaways from the post
  entities: {
    people: string[];                // People mentioned (e.g., ["John Smith", "Jane Doe"])
    websites: string[];              // Websites mentioned (e.g., ["apple.com", "google.com"])
    companies: string[];             // Companies mentioned (e.g., ["Apple", "Google"])
    other: string[];                 // Other significant entities
  }
}

interface BlogPostAnalysisVariables {
  postUrl: string;                   // URL of the blog post to analyze
  postHtml: string;                  // Raw HTML of the blog post
  extractedData: BlogPostData;       // Structured data extracted from the post
}
```

## Block Configurations

### Main Workflow

1. `start` - Manual invocation
   ```json
   {
     "runMode": "manual"
   }
   ```

2. `userMessage` (System) - Initialize Variables
   ```json
   {
     "message": "Initializing blog analysis for https://ggapps.co.uk/blog/",
     "source": "system",
     "mode": "foreground"
   }
   ```

3. `userMessage` - Set Initial Variables
   ```json
   {
     "message": "Setting up initial variables for the blog analysis.",
     "source": "system",
     "mode": "background",
     "destinationVar": "baseUrl",
     "structuredOutputType": "text"
   }
   ```

4. `scrapeUrl` - Get Initial Blog Page
   ```json
   {
     "url": "{{baseUrl}}",
     "destinationVar": "currentPageHtml"
   }
   ```

5. `userMessage` - Extract Blog Post URLs
   ```json
   {
     "message": "Extract all blog post URLs from the following HTML. Return only the complete URLs as a JSON array.\n\n<html>{{currentPageHtml}}</html>",
     "source": "user",
     "mode": "background",
     "destinationVar": "blogPostUrls",
     "structuredOutputType": "json"
   }
   ```

6. `userMessage` - Check for Next Page
   ```json
   {
     "message": "Analyze the following HTML and determine if there is a 'next page' or pagination link. If found, return the complete URL of the next page. If no next page exists, return 'none'.\n\n<html>{{currentPageHtml}}</html>",
     "source": "user",
     "mode": "background",
     "destinationVar": "nextPageUrl"
   }
   ```

7. `logic` - Check Pagination Result
   ```json
   {
     "prompt": "The next page URL is: {{nextPageUrl}}",
     "cases": [
       {
         "id": "hasNextPage",
         "condition": "A valid URL was found for the next page"
       },
       {
         "id": "noNextPage",
         "condition": "No next page was found or the value is 'none'"
       }
     ]
   }
   ```

8. `userMessage` (System) - Process Each Blog Post
   ```json
   {
     "message": "Processing {{totalPosts}} blog posts. This may take some time...",
     "source": "system",
     "mode": "foreground"
   }
   ```

9. `jump` - Process Individual Blog Post
   ```json
   {
     "workflow": "BlogPostAnalysis",
     "launchVariables": [
       {
         "key": "postUrl",
         "value": "{{currentPostUrl}}"
       }
     ]
   }
   ```

10. `userMessage` - Generate Executive Summary
    ```json
    {
      "message": "Create an executive summary of all the key findings and entities found across all blog posts. Focus on identifying patterns, common themes, and significant insights.\n\n<blogData>{{blogData}}</blogData>",
      "source": "user",
      "mode": "background",
      "destinationVar": "executiveSummary"
    }
    ```

11. `userMessage` (System) - Display Results
    ```json
    {
      "message": "# Blog Analysis Results\n\n## Executive Summary\n\n{{executiveSummary}}\n\n## Analyzed Blog Posts\n\n{{blogData}}",
      "source": "system",
      "mode": "foreground"
    }
    ```

12. `end`
    ```json
    {}
    ```

### Blog Post Analysis Workflow

1. `start` - Accept Blog Post URL
   ```json
   {
     "runMode": "api",
     "launchVariables": [
       {
         "key": "postUrl"
       }
     ]
   }
   ```

2. `scrapeUrl` - Scrape Blog Post
   ```json
   {
     "url": "{{postUrl}}",
     "destinationVar": "postHtml"
   }
   ```

3. `userMessage` - Extract Blog Information
   ```json
   {
     "message": "Extract the following information from this blog post HTML:\n1. Blog title\n2. Publication date\n3. Main text content\n\n<html>{{postHtml}}</html>\n\nReturn the information in JSON format with keys: 'title', 'date', and 'text'.",
     "source": "user",
     "mode": "background",
     "destinationVar": "basicInfo",
     "structuredOutputType": "json"
   }
   ```

4. `userMessage` - Extract Key Learnings
   ```json
   {
     "message": "Identify the key learnings or takeaways from this blog post. Return up to 5 clear, actionable points as a JSON array.\n\n<title>{{basicInfo.title}}</title>\n<content>{{basicInfo.text}}</content>",
     "source": "user",
     "mode": "background",
     "destinationVar": "keyLearnings",
     "structuredOutputType": "json"
   }
   ```

5. `userMessage` - Extract Entities
   ```json
   {
     "message": "Identify all named entities mentioned in this blog post, categorized by type. Focus on:\n1. People (individuals mentioned by name)\n2. Websites (domain names, URLs)\n3. Companies or organizations\n4. Other significant entities\n\n<title>{{basicInfo.title}}</title>\n<content>{{basicInfo.text}}</content>\n\nReturn in JSON format with these categories as keys, each containing an array of strings.",
     "source": "user",
     "mode": "background",
     "destinationVar": "entities",
     "structuredOutputType": "json"
   }
   ```

6. `userMessage` - Compile Complete Blog Data
   ```json
   {
     "message": "Combine the following data into a complete blog post record:\n<url>{{postUrl}}</url>\n<basicInfo>{{basicInfo}}</basicInfo>\n<keyLearnings>{{keyLearnings}}</keyLearnings>\n<entities>{{entities}}</entities>\n\nReturn as a single JSON object.",
     "source": "user",
     "mode": "background",
     "destinationVar": "extractedData",
     "structuredOutputType": "json"
   }
   ```

7. `end` - Return Results to Main Workflow
   ```json
   {
     "outputVariables": ["extractedData"]
   }
   ```

## Specific Notes

1. The target blog URL is https://ggapps.co.uk/blog/ with pagination following the pattern https://ggapps.co.uk/blog/page/2/
2. For each blog post, we extract:
    - Blog title
    - Blog date
    - Blog text
    - Key learnings (identified by AI)
    - Entities (people, websites, companies, etc.)
3. The output includes a detailed executive summary of findings and entities across all blog posts
4. The workflow uses pagination detection to navigate through all blog pages
5. Two separate workflows are used as recommended by the user's request for sub-workflows where appropriate

This design balances robustness with simplicity, uses AI for the analytical components, and ensures comprehensive data collection and summarization as requested.


# Overview

Green Gorilla Apps (GGA) is a UK-based software studio that designs and builds digital products for businesses—focusing on web apps, automation, and AI-driven solutions.

# Philosophy

Ship small, learn fast, automate relentlessly, and harness AI responsibly.

