<?php
/**
 * Blockchain Module for E-Voting System
 * 
 * This module provides blockchain functionality to ensure vote integrity,
 * transparency, and immutability. Each vote is recorded as a block in the chain.
 * 
 * @author Davis Kunyu
 * @version 1.0
 */

require_once 'functions.php';

/**
 * Block Class - Represents a single block in the blockchain
 */
class Block {
    public $index;
    public $timestamp;
    public $data; // Contains vote information
    public $previous_hash;
    public $hash;
    public $nonce;
    public $transaction_id;

    /**
     * Constructor for Block
     * 
     * @param int $index Block index in the chain
     * @param array $data Vote data (voter_id, candidate_id, etc.)
     * @param string $previous_hash Hash of the previous block
     */
    public function __construct($index, $data, $previous_hash = null) {
        $this->index = $index;
        $this->timestamp = time();
        $this->data = $data;
        $this->previous_hash = $previous_hash ? $previous_hash : '0';
        $this->nonce = 0;
        $this->transaction_id = $this->generateTransactionId();
        $this->hash = $this->calculateHash();
    }

    /**
     * Calculate the hash of the current block
     * Uses SHA-256 algorithm
     * 
     * @return string Block hash
     */
    public function calculateHash() {
        $block_string = $this->index . 
                       $this->timestamp . 
                       json_encode($this->data) . 
                       $this->previous_hash . 
                       $this->nonce .
                       $this->transaction_id;
        return hash('sha256', $block_string);
    }

    /**
     * Generate a unique transaction ID for the vote
     * 
     * @return string Transaction ID
     */
    private function generateTransactionId() {
        $data_string = json_encode($this->data) . $this->timestamp . uniqid();
        return hash('sha256', $data_string);
    }

    /**
     * Mine the block (Proof of Work)
     * In a production system, this would require solving a complex problem
     * For this voting system, we use a simplified version
     * 
     * @param int $difficulty Number of leading zeros required
     */
    public function mineBlock($difficulty = 2) {
        $target = str_repeat('0', $difficulty);
        
        while (substr($this->hash, 0, $difficulty) !== $target) {
            $this->nonce++;
            $this->hash = $this->calculateHash();
        }
        
        return $this->hash;
    }

    /**
     * Convert block to array for database storage
     * 
     * @return array Block data as array
     */
    public function toArray() {
        return [
            'index' => $this->index,
            'timestamp' => $this->timestamp,
            'data' => json_encode($this->data),
            'previous_hash' => $this->previous_hash,
            'hash' => $this->hash,
            'nonce' => $this->nonce,
            'transaction_id' => $this->transaction_id
        ];
    }
}

/**
 * Blockchain Class - Manages the blockchain
 */
class Blockchain {
    private $chain;
    private $db;
    private $difficulty;

    /**
     * Constructor for Blockchain
     * 
     * @param mysqli $db Database connection
     * @param int $difficulty Mining difficulty
     */
    public function __construct($db, $difficulty = 2) {
        $this->db = $db;
        $this->difficulty = $difficulty;
        $this->chain = [];
        $this->initializeBlockchain();
    }

    /**
     * Initialize the blockchain from database or create genesis block
     */
    private function initializeBlockchain() {
        // Create blockchain table if it doesn't exist
        $this->createBlockchainTable();
        
        // Load existing chain from database
        $this->loadChainFromDatabase();
        
        // If chain is empty, create genesis block
        if (empty($this->chain)) {
            $this->createGenesisBlock();
        }
    }

    /**
     * Create the blockchain table in database
     */
    private function createBlockchainTable() {
        $sql = "CREATE TABLE IF NOT EXISTS blockchain (
            id INT AUTO_INCREMENT PRIMARY KEY,
            block_index INT NOT NULL,
            timestamp BIGINT NOT NULL,
            data TEXT NOT NULL,
            previous_hash VARCHAR(64) NOT NULL,
            hash VARCHAR(64) NOT NULL UNIQUE,
            nonce INT NOT NULL,
            transaction_id VARCHAR(64) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_block_index (block_index),
            INDEX idx_hash (hash),
            INDEX idx_transaction_id (transaction_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (!mysqli_query($this->db, $sql)) {
            error_log("Failed to create blockchain table: " . mysqli_error($this->db));
        }
    }

    /**
     * Load blockchain from database
     */
    private function loadChainFromDatabase() {
        $sql = "SELECT * FROM blockchain ORDER BY block_index ASC";
        $result = mysqli_query($this->db, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $block_data = json_decode($row['data'], true);
                $block = new Block(
                    $row['block_index'],
                    $block_data,
                    $row['previous_hash']
                );
                $block->hash = $row['hash'];
                $block->nonce = $row['nonce'];
                $block->transaction_id = $row['transaction_id'];
                $block->timestamp = $row['timestamp'];
                
                $this->chain[] = $block;
            }
        }
    }

    /**
     * Create the genesis block (first block in the chain)
     */
    private function createGenesisBlock() {
        $genesis_data = [
            'type' => 'genesis',
            'message' => 'Genesis block for E-Voting System',
            'election_id' => 'ELECTION_' . date('YmdHis'),
            'created_by' => 'SYSTEM'
        ];
        
        $genesis_block = new Block(0, $genesis_data, '0');
        $genesis_block->mineBlock($this->difficulty);
        $this->addBlock($genesis_block);
    }

    /**
     * Get the latest block in the chain
     * 
     * @return Block|null Latest block
     */
    public function getLatestBlock() {
        return !empty($this->chain) ? end($this->chain) : null;
    }

    /**
     * Add a new block to the blockchain
     * 
     * @param Block $new_block The new block to add
     * @return bool True if successful, false otherwise
     */
    public function addBlock($new_block) {
        $latest_block = $this->getLatestBlock();
        
        if ($latest_block) {
            $new_block->previous_hash = $latest_block->hash;
            $new_block->index = $latest_block->index + 1;
        }
        
        // Mine the block
        $new_block->mineBlock($this->difficulty);
        
        // Validate block before adding
        if ($this->isValidBlock($new_block, $latest_block)) {
            // Save to database
            if ($this->saveBlockToDatabase($new_block)) {
                $this->chain[] = $new_block;
                return true;
            }
        }
        
        return false;
    }

    /**
     * Create and add a vote block to the blockchain
     * 
     * @param int $voter_id Voter ID
     * @param int $candidate_id Candidate ID
     * @param array $additional_data Additional vote data
     * @return array|false Block information or false on failure
     */
    public function addVoteBlock($voter_id, $candidate_id, $additional_data = []) {
        $vote_data = [
            'type' => 'vote',
            'voter_id' => $voter_id,
            'candidate_id' => $candidate_id,
            'timestamp' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100)
        ];
        
        // Merge additional data
        $vote_data = array_merge($vote_data, $additional_data);
        
        $latest_block = $this->getLatestBlock();
        $previous_hash = $latest_block ? $latest_block->hash : '0';
        $next_index = $latest_block ? $latest_block->index + 1 : 1;
        
        $new_block = new Block($next_index, $vote_data, $previous_hash);
        
        if ($this->addBlock($new_block)) {
            return [
                'transaction_id' => $new_block->transaction_id,
                'block_index' => $new_block->index,
                'hash' => $new_block->hash,
                'timestamp' => $new_block->timestamp
            ];
        }
        
        return false;
    }

    /**
     * Validate a block
     * 
     * @param Block $new_block The new block to validate
     * @param Block|null $previous_block The previous block
     * @return bool True if valid, false otherwise
     */
    public function isValidBlock($new_block, $previous_block = null) {
        // Check if block hash is valid
        if ($new_block->hash !== $new_block->calculateHash()) {
            error_log("Invalid block hash for block {$new_block->index}");
            return false;
        }
        
        // Check if previous hash matches
        if ($previous_block && $new_block->previous_hash !== $previous_block->hash) {
            error_log("Invalid previous hash for block {$new_block->index}");
            return false;
        }
        
        // Check if block index is correct
        if ($previous_block && $new_block->index !== $previous_block->index + 1) {
            error_log("Invalid block index for block {$new_block->index}");
            return false;
        }
        
        return true;
    }

    /**
     * Validate the entire blockchain
     * 
     * @return array Validation result with status and issues
     */
    public function isValidChain() {
        $issues = [];
        
        for ($i = 1; $i < count($this->chain); $i++) {
            $current_block = $this->chain[$i];
            $previous_block = $this->chain[$i - 1];
            
            // Validate current block
            if (!$this->isValidBlock($current_block, $previous_block)) {
                $issues[] = "Invalid block at index {$current_block->index}";
            }
            
            // Check hash integrity
            if ($current_block->hash !== $current_block->calculateHash()) {
                $issues[] = "Hash mismatch at block index {$current_block->index}";
            }
        }
        
        return [
            'is_valid' => empty($issues),
            'issues' => $issues,
            'chain_length' => count($this->chain)
        ];
    }

    /**
     * Save block to database
     * 
     * @param Block $block Block to save
     * @return bool True if successful, false otherwise
     */
    private function saveBlockToDatabase($block) {
        $block_array = $block->toArray();
        
        $sql = "INSERT INTO blockchain (
            block_index, 
            timestamp, 
            data, 
            previous_hash, 
            hash, 
            nonce, 
            transaction_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param(
            "iisssis",
            $block_array['index'],
            $block_array['timestamp'],
            $block_array['data'],
            $block_array['previous_hash'],
            $block_array['hash'],
            $block_array['nonce'],
            $block_array['transaction_id']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        if (!$result) {
            error_log("Failed to save block to database: " . mysqli_error($this->db));
        }
        
        return $result;
    }

    /**
     * Get blockchain statistics
     * 
     * @return array Blockchain statistics
     */
    public function getStatistics() {
        $latest_block = $this->getLatestBlock();
        $validation = $this->isValidChain();
        
        // Get vote count from blockchain
        $vote_count_sql = "SELECT COUNT(*) as count FROM blockchain WHERE data LIKE '%\"type\":\"vote\"%'";
        $vote_result = mysqli_query($this->db, $vote_count_sql);
        $vote_count = 0;
        if ($vote_result) {
            $row = mysqli_fetch_assoc($vote_result);
            $vote_count = $row['count'];
        }
        
        // Get total blocks
        $total_blocks_sql = "SELECT COUNT(*) as count FROM blockchain";
        $total_result = mysqli_query($this->db, $total_blocks_sql);
        $total_blocks = 0;
        if ($total_result) {
            $row = mysqli_fetch_assoc($total_result);
            $total_blocks = $row['count'];
        }
        
        return [
            'total_blocks' => $total_blocks,
            'vote_blocks' => $vote_count,
            'latest_block_index' => $latest_block ? $latest_block->index : 0,
            'latest_block_hash' => $latest_block ? $latest_block->hash : 'N/A',
            'chain_valid' => $validation['is_valid'],
            'validation_issues' => count($validation['issues']),
            'difficulty' => $this->difficulty
        ];
    }

    /**
     * Get block by transaction ID
     * 
     * @param string $transaction_id Transaction ID
     * @return array|false Block data or false if not found
     */
    public function getBlockByTransactionId($transaction_id) {
        $sql = "SELECT * FROM blockchain WHERE transaction_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("s", $transaction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result && $row = $result->fetch_assoc()) {
            return [
                'block_index' => $row['block_index'],
                'transaction_id' => $row['transaction_id'],
                'hash' => $row['hash'],
                'timestamp' => $row['timestamp'],
                'data' => json_decode($row['data'], true),
                'previous_hash' => $row['previous_hash']
            ];
        }
        
        return false;
    }

    /**
     * Get recent blocks
     * 
     * @param int $limit Number of recent blocks to retrieve
     * @return array Array of recent blocks
     */
    public function getRecentBlocks($limit = 10) {
        $sql = "SELECT * FROM blockchain ORDER BY block_index DESC LIMIT ?";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        $blocks = [];
        while ($row = $result->fetch_assoc()) {
            $blocks[] = [
                'block_index' => $row['block_index'],
                'transaction_id' => $row['transaction_id'],
                'hash' => substr($row['hash'], 0, 16) . '...',
                'timestamp' => $row['timestamp'],
                'data' => json_decode($row['data'], true),
                'created_at' => $row['created_at']
            ];
        }
        
        return $blocks;
    }
}

/**
 * Initialize and return blockchain instance
 * 
 * @return Blockchain Blockchain instance
 */
function getBlockchain() {
    global $db;
    static $blockchain = null;
    
    if ($blockchain === null) {
        $blockchain = new Blockchain($db, 2); // Difficulty level 2
    }
    
    return $blockchain;
}

/**
 * Record a vote on the blockchain
 * 
 * @param int $voter_id Voter ID
 * @param int $candidate_id Candidate ID
 * @return array|false Transaction information or false on failure
 */
function recordVoteOnBlockchain($voter_id, $candidate_id) {
    try {
        $blockchain = getBlockchain();
        $result = $blockchain->addVoteBlock($voter_id, $candidate_id);
        return $result;
    } catch (Exception $e) {
        error_log("Error recording vote on blockchain: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify blockchain integrity
 * 
 * @return array Verification result
 */
function verifyBlockchainIntegrity() {
    try {
        $blockchain = getBlockchain();
        return $blockchain->isValidChain();
    } catch (Exception $e) {
        error_log("Error verifying blockchain integrity: " . $e->getMessage());
        return ['is_valid' => false, 'issues' => ['Error: ' . $e->getMessage()], 'chain_length' => 0];
    }
}

/**
 * Get blockchain statistics for monitoring
 * 
 * @return array Blockchain statistics
 */
function getBlockchainStatistics() {
    try {
        $blockchain = getBlockchain();
        return $blockchain->getStatistics();
    } catch (Exception $e) {
        error_log("Error getting blockchain statistics: " . $e->getMessage());
        return [
            'total_blocks' => 0,
            'vote_blocks' => 0,
            'latest_block_index' => 0,
            'latest_block_hash' => 'N/A',
            'chain_valid' => false,
            'validation_issues' => 1,
            'difficulty' => 2
        ];
    }
}

/**
 * Get transaction details by transaction ID
 * 
 * @param string $transaction_id Transaction ID
 * @return array|false Transaction details or false if not found
 */
function getTransactionDetails($transaction_id) {
    try {
        $blockchain = getBlockchain();
        return $blockchain->getBlockByTransactionId($transaction_id);
    } catch (Exception $e) {
        error_log("Error getting transaction details: " . $e->getMessage());
        return false;
    }
}

?>
