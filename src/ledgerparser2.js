import { BigNumber } from 'bignumber.js'

const parseAmount = function(amountString, currencyStyles) {
	// Just defaults, in case no style is found
	let decimalSeparator = '.'
	let groupSeparator = ','
	const parsedAmount = {}
	const matches = [...amountString.matchAll(/\d+/g)]
	if (matches.length === 0) {
		return null
	}
	parsedAmount.prefix = amountString.substring(0, matches[0].index)
	parsedAmount.postfix = amountString.substring(matches[matches.length - 1].index + matches[matches.length - 1][0].length)
	parsedAmount.stringAmount = amountString.substring(matches[0].index, matches[matches.length - 1].index + matches[matches.length - 1][0].length)

	// Remove negative symbol from prefix and put it on the amount
	if (parsedAmount.prefix.indexOf('-') >= 0) {
		parsedAmount.prefix = parsedAmount.prefix.split('-').join('')
		parsedAmount.stringAmount = '-' + parsedAmount.stringAmount
	}
	parsedAmount.prefix = parsedAmount.prefix.trimStart()
	parsedAmount.postfix = parsedAmount.postfix.trimEnd()
	parsedAmount.currency = ''
	if (parsedAmount.prefix.length > 0) {
		parsedAmount.currency = parsedAmount.prefix.trim()
	} else if (parsedAmount.postfix.length > 0) {
		parsedAmount.currency = parsedAmount.postfix.trim()
	}
	if (currencyStyles[parsedAmount.currency]) {
		decimalSeparator = currencyStyles[parsedAmount.currency].decimalSeparator
		groupSeparator = currencyStyles[parsedAmount.currency].groupSeparator
	}
	let noCurrencyAmount = parsedAmount.stringAmount
	// Remove any group separators to parse it with BigNumber
	noCurrencyAmount = noCurrencyAmount.split(groupSeparator).join('')
	// Replace decimal separator with '.' to parse it with BigNumber
	if (decimalSeparator !== '.') {
		noCurrencyAmount = noCurrencyAmount.split(decimalSeparator).join('.')
	}
	parsedAmount.bn = BigNumber(noCurrencyAmount)

	return parsedAmount
}

const serializeAmount = function(amountObj, currencyStyles) {
	let amountString = ''
	let currencyStyle = { decimalSeparator: '.', groupSeparator: ',', decimalSize: 2, groupSize: 3 }

	if (currencyStyles[amountObj.currency]) {
		currencyStyle = currencyStyles[amountObj.currency]
	}

	if (amountObj === null) {
		return ''
	}
	amountString += amountObj.prefix
	amountString += amountObj.bn.toFormat(currencyStyle.decimalSize, null, {
		decimalSeparator: currencyStyle.decimalSeparator,
		groupSeparator: currencyStyle.groupSeparator,
		groupSize: currencyStyle.groupSize
	})
	amountString += amountObj.postfix
	return amountString
}

const fromAmount = function(amountString, currencyStyles = {}) {
	const amountObject = {}
	const equalSplit = amountString.split(/(==\*|=\*|==|=)/g)
	const priceSplit = equalSplit[0].split(/(\(@@\)|\(@\)|@@|@)/g)
	amountObject.amount = parseAmount(priceSplit[0], currencyStyles)
	if (priceSplit.length > 1) {
		amountObject.amountPrice = parseAmount(priceSplit[2], currencyStyles)
		amountObject.amountPriceType = priceSplit[1]
	}
	if (equalSplit.length > 1) {
		const priceSplitAssert = equalSplit[2].split(/(\(@@\)|\(@\)|@@|@)/g)
		amountObject.assertAssign = parseAmount(priceSplitAssert[0], currencyStyles)
		if (priceSplitAssert.length > 1) {
			amountObject.assertAssignPrice = parseAmount(priceSplitAssert[2], currencyStyles)
			amountObject.assertAssignPriceType = priceSplitAssert[1]
		}
		amountObject.equalType = equalSplit[1]
	}
	if (amountObject.amount === null) {
		return null
	}
	return amountObject
}

const toAmount = function(amountObject, currencyStyles = {}) {
	let amountString = ''
	if (amountObject === null) {
		return ''
	}
	if (amountObject.amount) {
		amountString += serializeAmount(amountObject.amount, currencyStyles)
	}
	if (amountObject.amountPrice) {
		amountString += ' ' + amountObject.amountPriceType + ' ' + serializeAmount(amountObject.amountPrice, currencyStyles)
	}
	if (amountObject.assertAssign) {
		amountString += ' ' + amountObject.equalType + ' ' + serializeAmount(amountObject.assertAssign, currencyStyles)
		if (amountObject.assertAssignPrice) {
			amountString += ' ' + amountObject.assertAssignPriceType + ' ' + serializeAmount(amountObject.assertAssignPrice, currencyStyles)
		}
	}
	return amountString
}

const fromLedger = function(ledger, normalize = true) {
	const blocks = []
	const transactionIndexes = []
	const lines = ledger.split(/\r?\n/)
	const accountList = {}
	const txnRegex = /^([0-9]+[-\\/][0-9]+[-\\/][0-9]+)( (!|\*)?( )?(!|\*)?( )?(\(.*\))?( )?([^;]*))?(;(.*)?)?$/
	const allAmounts = []
	const separatorStyles = {}

	for (let i = 0; i < lines.length; i++) {
		const txnRegexResult = txnRegex.exec(lines[i])

		// Test to see if the first line of this block matches a transaction
		if (txnRegexResult) {
			// Vuetify does not like slashes in dates
			const thisDate = txnRegexResult[1].replace(/\//g, '-')
			blocks.push({
				type: 'transaction',
				date: thisDate,
				status: ((txnRegexResult[3] ? txnRegexResult[3] : '')
								+ (normalize ? ' ' : (txnRegexResult[4] ? txnRegexResult[4] : ''))
								+ (txnRegexResult[5] ? txnRegexResult[5] : '')).trim(),
				code: (txnRegexResult[7] ? txnRegexResult[7].substring(1, txnRegexResult[7].length - 1) : ''),
				description: (txnRegexResult[9] ? txnRegexResult[9] : ''),
				comment: (txnRegexResult[11] ? txnRegexResult[11] : ''),
				lines: [],
				postingIndexes: []
			})
			/* Normalize the order of the status */
			if (normalize && blocks[blocks.length - 1].status === '* !') {
				blocks[blocks.length - 1].status = '! *'
			}
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
				if (amount.length > 0) {
					// Unfortunately we don't know decimal or group separators yet so this parsedAmount is trash except for the surrounding currency
					let parsedAmount = fromAmount(amount)
					if (parsedAmount !== null) {
						parsedAmount = parsedAmount.amount
						if (!separatorStyles[parsedAmount.currency]) {
							const separatorStyle = { votesDotDecimalSeparator: 0, votesCommaDecimalSeparator: 0 }
							separatorStyles[parsedAmount.currency] = separatorStyle
						}
						const separatorStyle = separatorStyles[parsedAmount.currency]

						if (amount.lastIndexOf('.') > amount.lastIndexOf(',') && (amount.match(/\./g) || []).length === 1) {
							separatorStyle.votesDotDecimalSeparator++
						} else if (amount.lastIndexOf(',') > amount.lastIndexOf('.') && (amount.match(/,/g) || []).length === 1) {
							separatorStyle.votesCommaDecimalSeparator++
						}
						allAmounts.push(parsedAmount)
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
	const currencyStyles = {}
	for (const cur in separatorStyles) {
		const currencyStyle = { decimalSeparator: '.', groupSeparator: ',', decimalSize: 2, groupSize: 3 }
		if (separatorStyles[cur].votesCommaDecimalSeparator > separatorStyles[cur].votesDotDecimalSeparator) {
			currencyStyle.decimalSeparator = ','
			currencyStyle.groupSeparator = '.'
		}
		currencyStyles[cur] = currencyStyle
	}

	const decimalSizeVotes = {}
	const groupSizeVotes = {}

	for (let i = 0; i < allAmounts.length; i++) {
		const noCurrencyAmount = allAmounts[i].stringAmount
		let noDecimalAmount = noCurrencyAmount
		if (noCurrencyAmount.lastIndexOf(currencyStyles[allAmounts[i].currency].decimalSeparator) >= 0) {
			const vote = (noCurrencyAmount.length - noCurrencyAmount.lastIndexOf(currencyStyles[allAmounts[i].currency].decimalSeparator) - 1).toString() + '_' + allAmounts[i].currency
			if (!decimalSizeVotes[vote]) {
				decimalSizeVotes[vote] = 0
			}
			decimalSizeVotes[vote]++
			noDecimalAmount = noCurrencyAmount.substring(0, noCurrencyAmount.lastIndexOf(currencyStyles[allAmounts[i].currency].decimalSeparator))
		}
		if (noDecimalAmount.lastIndexOf(currencyStyles[allAmounts[i].currency].groupSeparator) >= 0) {
			const vote = (noDecimalAmount.length - noDecimalAmount.lastIndexOf(currencyStyles[allAmounts[i].currency].groupSeparator) - 1).toString() + '_' + allAmounts[i].currency
			if (!groupSizeVotes[vote]) {
				groupSizeVotes[vote] = 0
			}
			groupSizeVotes[vote]++
		}
	}

	for (const cur in currencyStyles) {
		let maxKey = null
		for (const key in decimalSizeVotes) {
			if (key.endsWith('_' + cur) && (maxKey === null || decimalSizeVotes[key] > decimalSizeVotes[maxKey])) {
				maxKey = key
			}
		}
		if (maxKey !== null) {
			currencyStyles[cur].decimalSize = Number(maxKey.split('_')[0])
		}

		maxKey = null
		for (const key in groupSizeVotes) {
			if (key.endsWith('_' + cur) && (maxKey === null || groupSizeVotes[key] > groupSizeVotes[maxKey])) {
				maxKey = key
			}
		}
		if (maxKey !== null) {
			currencyStyles[cur].groupSize = Number(maxKey.split('_')[0])
		}
	}

	return { blocks, transactionIndexes, accounts: Object.keys(accountList), currencyStyles }
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

export { fromLedger, toLedger, fromAmount, toAmount }
