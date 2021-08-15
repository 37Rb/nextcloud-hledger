const fromLedger = function(ledger) {
	const blocks = []
	const transactionIndexes = []
	const lines = ledger.split(/\r?\n/)
	const accountList = {}
	const txnRegex = /^([0-9]+[-\\/][0-9]+[-\\/][0-9]+)( (!|\*)?( )?(\(.*\) )?([^;]*))?(;(.*)?)?$/
	for (let i = 0; i < lines.length; i++) {
		const txnRegexResult = txnRegex.exec(lines[i])

		// Test to see if the first line of this block matches a transaction
		if (txnRegexResult) {
			// Vuetify does not like slashes in dates
			const thisDate = txnRegexResult[1].replace(/\//g, '-')
			blocks.push({
				type: 'transaction',
				date: thisDate,
				status: (txnRegexResult[3] ? txnRegexResult[3] : ''),
				code: (txnRegexResult[5] ? txnRegexResult[5].substring(1, txnRegexResult[5].length - 2) : ''),
				description: (txnRegexResult[6] ? txnRegexResult[6] : ''),
				comment: (txnRegexResult[8] ? txnRegexResult[8] : ''),
				lines: [],
				postingIndexes: []
			})
			// Save a list of blocks which are transactions for ease of processing
			transactionIndexes.push(blocks.length - 1)
		// Test to see if this line is indented, which means it can be nested within a previously created block
		} else if (lines[i].match(/^( {2}|\t)/) && blocks.length > 0 && (blocks[blocks.length - 1].type === 'transaction' || blocks[blocks.length - 1].type === 'other')) {

			let line = lines[i].trimStart()
			// Special case for indented comment line
			if (line[0] === ';') {
				blocks[blocks.length - 1].lines.push({ type: 'comment', text: line.substring(1) })
			} else {
				// Look for posting status
				let status = ''
				if (line.length > 2) {
					const candidateStatus = line.substring(0, 2)
					if (candidateStatus === '! ' || candidateStatus === '* ') {
						status = candidateStatus.substring(0, 1)
						line = line.substring(2)
					}
				}
				// Look for amounts
				const postingSplitRegex = /\s*( {2}|\t)\s*/g
				const group = postingSplitRegex.exec(line)
				let account = null
				let remainingPart = ''
				let amount = ''
				if (group && group.index) {
					account = line.slice(0, group.index)
					remainingPart = line.slice(postingSplitRegex.lastIndex)
					amount = remainingPart.trimEnd()
				} else {
					account = line
				}
				// Look for comment
				const commentRegex = /^([^;]*)(;)?(.*)?$/
				let comment = ''
				if (remainingPart.length > 0) {
					const remaining = commentRegex.exec(remainingPart)
					amount = remaining[1].trimEnd()
					if (remaining[3] && remaining[3].length > 0) {
						comment = remaining[3]
					}
				}
				blocks[blocks.length - 1].lines.push({ type: 'posting', account, amount, comment, status })
				if (blocks[blocks.length - 1].type === 'transaction') {
					accountList[account] = 1
				}

				// Save a list of lines which are postings for ease of processing
				blocks[blocks.length - 1].postingIndexes.push(blocks[blocks.length - 1].lines.length - 1)
			}
			// After encountering an indented line, remember a single blank line
			if (lines.length > i + 1 && lines[i + 1].trim() === '') {
				i++
				blocks[blocks.length - 1].trailingBlankLine = true
			}
		// Test to see if this is a comment block
		} else if (lines[i].trimStart()[0] === ';' || lines[i].trimStart()[0] === '#' || lines[i].trimStart()[0] === '*') {
			// Special case to add onto a previous comment block if there was one above this line
			if (blocks.length > 0 && blocks[blocks.length - 1].type === 'comment' && blocks[blocks.length - 1].commentType === lines[i].trimStart()[0]) {
				blocks[blocks.length - 1].text += '\n' + lines[i].trimStart().substring(1)
			} else {
				blocks.push({ type: 'comment', text: lines[i].trimStart().substring(1), commentType: lines[i].trimStart()[0] })
			}
		} else if (lines[i] === 'comment') {
			let commentText = ''
			i++
			while (i < lines.length && lines[i] !== 'end comment') {
				commentText += lines[i] + '\n'
				i++
			}
			blocks.push({ type: 'comment', text: commentText.substring(0, commentText.length - 1), commentType: 'multiline' })
		// Define a catch-all block type since we don't know how to parse everything that is valid
		} else {
			// Special case to add onto a previous 'other' block if there was one directly above this line
			if (blocks.length > 0 && blocks[blocks.length - 1].type === 'other' && blocks[blocks.length - 1].lines.length === 0) {
				blocks[blocks.length - 1].text += '\n' + lines[i]
			} else {
				blocks.push({ type: 'other', text: lines[i], lines: [], postingIndexes: [] })
			}
		}
	}
	return { blocks, transactionIndexes, accounts: Object.keys(accountList) }
}

const toLedger = function(objs) {
	let ledger = ''
	for (let i = 0; i < objs.blocks.length; i++) {
		if (objs.blocks[i].type === 'comment') {
			// Restore a comment as close to how it originally was
			if (objs.blocks[i].commentType === 'multiline') {
				ledger += 'comment\n' + objs.blocks[i].text + '\nend comment\n'
			} else {
				ledger += objs.blocks[i].commentType + objs.blocks[i].text.split(/\r?\n/).join('\n' + objs.blocks[i].commentType) + '\n'
			}
		} else if (objs.blocks[i].type === 'transaction' || objs.blocks[i].type === 'other') {
			// Reconstruct a transaction or 'other' unknown structure of data
			if (objs.blocks[i].type === 'transaction') {
				ledger += objs.blocks[i].date + ' ' + (objs.blocks[i].status.length > 0 ? objs.blocks[i].status + ' ' : '')
					+ (objs.blocks[i].code.length > 0 ? '(' + objs.blocks[i].code + ') ' : '')
					+ (objs.blocks[i].description.length > 0 ? objs.blocks[i].description : '')
					+ (objs.blocks[i].comment.length > 0 ? ';' + objs.blocks[i].comment : '') + '\n'
			} else if (objs.blocks[i].type === 'other') {
				ledger += objs.blocks[i].text + '\n'
			}
			if (objs.blocks[i].lines) {
				let longestAccount = 0
				let longestAmount = 0
				for (let j = 0; j < objs.blocks[i].lines.length; j++) {
					if (objs.blocks[i].lines[j].type === 'posting') {
						const candidateLongestAccount = objs.blocks[i].lines[j].account.length + (objs.blocks[i].lines[j].status.length > 0 ? 2 : 0)
						if (candidateLongestAccount > longestAccount) {
							longestAccount = candidateLongestAccount
						}
						if (objs.blocks[i].lines[j].amount.length > longestAmount) {
							longestAmount = objs.blocks[i].lines[j].amount.length
						}
					}
				}
				for (let j = 0; j < objs.blocks[i].lines.length; j++) {
					if (objs.blocks[i].lines[j].type === 'posting') {
						ledger += '    ' + (objs.blocks[i].lines[j].status.length > 0 ? objs.blocks[i].lines[j].status.substring(0, 1) + ' ' : '') + objs.blocks[i].lines[j].account
						const statusLength = objs.blocks[i].lines[j].status.length > 0 ? 2 : 0
						if (objs.blocks[i].lines[j].amount.length > 0) {
							if (objs.blocks[i].lines[j].account.length < longestAccount) {
								ledger += ' '.repeat(longestAccount - objs.blocks[i].lines[j].account.length - statusLength)
							}

							if (longestAmount - objs.blocks[i].lines[j].amount.length > 0) {
								ledger += ' '.repeat(longestAmount - objs.blocks[i].lines[j].amount.length)
							}
							ledger += '  ' + objs.blocks[i].lines[j].amount
						}
						if (objs.blocks[i].lines[j].comment.length > 0) {
							// Special case if there was no amount but there is a comment
							if (objs.blocks[i].lines[j].amount.length === 0) {
								ledger += ' '.repeat(longestAccount - objs.blocks[i].lines[j].account.length - statusLength + 2 + longestAmount)
							}
							ledger += ' ;' + objs.blocks[i].lines[j].comment
						}
						ledger += '\n'
					} else if (objs.blocks[i].lines[j].type === 'comment') {
						ledger += '    ;' + objs.blocks[i].lines[j].text + '\n'
					}
				}
				if (objs.blocks[i].trailingBlankLine) {
					ledger += '\n'
				}
			}
		}
	}
	// Remove final newline which was added due to line splitting approach
	if (ledger[ledger.length - 1] === '\n') {
		ledger = ledger.substr(0, ledger.length - 1)
	}
	return ledger
}

export { fromLedger, toLedger }
